<?php

declare(strict_types=1);

namespace Tests\Unit\Community\Posts;

use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Community\Posts\Services\PostReadService;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Community\Posts\DTOs\PostDetailResultDTO;
use App\Domain\Community\Posts\DTOs\PostListResultDTO;
use App\Domain\Community\Posts\DTOs\PostPageDTO;
use App\Domain\Community\Posts\Enums\PostTopic;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Community\Posts\Queries\PostQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\Viewer;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\TestCase;

class PostReadServiceTest extends TestCase
{
    private PostRepositoryInterface&MockObject $repository;

    private LoadEntityStatsAction&MockObject $loadEntityStats;

    private HashtagServiceInterface&MockObject $hashtagService;

    private IncrementViewAction&MockObject $incrementView;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(PostRepositoryInterface::class);
        $this->loadEntityStats = $this->createMock(LoadEntityStatsAction::class);
        $this->hashtagService = $this->createMock(HashtagServiceInterface::class);
        $this->incrementView = $this->createMock(IncrementViewAction::class);
    }

    public function test_list_enrichment_is_batched_once_per_page(): void
    {
        $posts = [$this->domainPost(1), $this->domainPost(2), $this->domainPost(3)];

        $this->repository->method('find')->willReturn(new PostPageDTO($posts, $this->pagination(3)));

        $this->loadEntityStats->expects(self::once())
            ->method('batchLoadStatsById')
            ->with((string) ObjectTemplateType::POST->getLegacyId(), [1, 2, 3])
            ->willReturn([
                1 => ['likes' => 4, 'downloads' => 0, 'views' => 10, 'comments' => 2],
                2 => ['likes' => 0, 'downloads' => 0, 'views' => 0, 'comments' => 0],
            ]);

        $this->hashtagService->expects(self::once())
            ->method('getBatchHashtags')
            ->with([1, 2, 3], ObjectTemplateType::POST)
            ->willReturn([1 => [$this->hashtag(1, '#a')]]);

        $result = $this->service()->find(PostQueryCriteria::forListing());

        self::assertTrue($result->isSuccess());

        /** @var PostListResultDTO $data */
        $data = $result->getData();

        self::assertCount(3, $data->items);
        self::assertSame(4, $data->items[0]->stats->getLikesCount());
        self::assertSame(10, $data->items[0]->stats->getViewsCount());
        self::assertSame(2, $data->items[0]->stats->getCommentsCount());
        self::assertSame(0, $data->items[0]->stats->getDownloadsCount());
    }

    public function test_posts_without_stats_or_hashtags_are_zero_filled(): void
    {
        $this->repository->method('find')->willReturn(new PostPageDTO([$this->domainPost(9)], $this->pagination(1)));
        $this->loadEntityStats->method('batchLoadStatsById')->willReturn([]);
        $this->hashtagService->method('getBatchHashtags')->willReturn([]);

        /** @var PostListResultDTO $data */
        $data = $this->service()->find(PostQueryCriteria::forListing())->getData();

        self::assertSame(0, $data->items[0]->stats->getLikesCount());
        self::assertSame(0, $data->items[0]->stats->getViewsCount());
        self::assertSame(0, $data->items[0]->stats->getCommentsCount());
        self::assertSame([], $data->items[0]->hashtags);
    }

    public function test_an_empty_page_skips_enrichment_entirely(): void
    {
        $this->repository->method('find')->willReturn(new PostPageDTO([], $this->pagination(0)));

        $this->loadEntityStats->expects(self::never())->method('batchLoadStatsById');
        $this->hashtagService->expects(self::never())->method('getBatchHashtags');

        /** @var PostListResultDTO $data */
        $data = $this->service()->find(PostQueryCriteria::forListing())->getData();

        self::assertSame([], $data->items);
        self::assertSame(0, $data->pagination['total']);
    }

    public function test_list_items_carry_at_most_three_hashtags(): void
    {
        $this->repository->method('find')->willReturn(new PostPageDTO([$this->domainPost(1)], $this->pagination(1)));
        $this->loadEntityStats->method('batchLoadStatsById')->willReturn([]);
        $this->hashtagService->method('getBatchHashtags')->willReturn([
            1 => [
                $this->hashtag(1, '#a'),
                $this->hashtag(2, '#b'),
                $this->hashtag(3, '#c'),
                $this->hashtag(4, '#d'),
            ],
        ]);

        /** @var PostListResultDTO $data */
        $data = $this->service()->find(PostQueryCriteria::forListing())->getData();

        self::assertCount(3, $data->items[0]->hashtags);
        self::assertSame('#a', $data->items[0]->hashtags[0]->content);
    }

    public function test_detail_returns_every_hashtag_and_records_the_view_before_reading_stats(): void
    {
        $post = $this->domainPost(5);
        $this->repository->method('findByUuid')->willReturn($post);

        $callOrder = [];

        $this->incrementView->expects(self::once())
            ->method('execute')
            ->with(5, ObjectTemplateType::POST, self::isInstanceOf(Viewer::class))
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'view';
            });

        $this->loadEntityStats->expects(self::once())
            ->method('batchLoadStatsById')
            ->willReturnCallback(function () use (&$callOrder): array {
                $callOrder[] = 'stats';

                return [5 => ['likes' => 1, 'downloads' => 0, 'views' => 1, 'comments' => 0]];
            });

        $this->hashtagService->expects(self::once())
            ->method('getHashtags')
            ->with(5, ObjectTemplateType::POST)
            ->willReturn([$this->hashtag(1, '#a'), $this->hashtag(2, '#b'), $this->hashtag(3, '#c'), $this->hashtag(4, '#d')]);

        $result = $this->service()->findByIdentifier($post->getUuid()->value(), $this->authenticatedViewer());

        self::assertTrue($result->isSuccess());

        /** @var PostDetailResultDTO $data */
        $data = $result->getData();

        self::assertCount(4, $data->hashtags);
        self::assertSame(['view', 'stats'], $callOrder);
    }

    public function test_anonymous_detail_requests_record_no_view(): void
    {
        $this->repository->method('findByUuid')->willReturn($this->domainPost(5));
        $this->loadEntityStats->method('batchLoadStatsById')->willReturn([]);
        $this->hashtagService->method('getHashtags')->willReturn([]);

        $this->incrementView->expects(self::never())->method('execute');

        $result = $this->service()->findByIdentifier(
            '922f91b4-cbe8-4ca0-8bf8-50de48f5d086',
            new Viewer(null, '127.0.0.1'),
        );

        self::assertTrue($result->isSuccess());
    }

    public function test_a_failing_view_write_does_not_fail_the_read(): void
    {
        $this->repository->method('findByUuid')->willReturn($this->domainPost(5));
        $this->loadEntityStats->method('batchLoadStatsById')->willReturn([]);
        $this->hashtagService->method('getHashtags')->willReturn([]);
        $this->incrementView->method('execute')->willThrowException(new RuntimeException('views table is down'));

        $result = $this->service()->findByIdentifier(
            '922f91b4-cbe8-4ca0-8bf8-50de48f5d086',
            $this->authenticatedViewer(),
        );

        self::assertTrue($result->isSuccess());
    }

    public function test_a_numeric_identifier_resolves_through_the_legacy_lookup(): void
    {
        $this->repository->expects(self::once())->method('findByLegacyId')->with(42)->willReturn($this->domainPost(42));
        $this->repository->expects(self::never())->method('findByUuid');
        $this->loadEntityStats->method('batchLoadStatsById')->willReturn([]);
        $this->hashtagService->method('getHashtags')->willReturn([]);

        $result = $this->service()->findByIdentifier('42', new Viewer(null, '127.0.0.1'));

        self::assertTrue($result->isSuccess());
    }

    public function test_malformed_identifiers_fail_before_any_repository_work(): void
    {
        $this->repository->expects(self::never())->method('findByUuid');
        $this->repository->expects(self::never())->method('findByLegacyId');

        foreach (['not-a-uuid', '0', '-3', ''] as $identifier) {
            $result = $this->service()->findByIdentifier($identifier, new Viewer(null, '127.0.0.1'));

            self::assertTrue($result->isFailure(), "identifier: {$identifier}");
            self::assertSame('INVALID_POST_IDENTIFIER', $result->getError()->code);
        }
    }

    public function test_unresolvable_identifiers_report_not_found(): void
    {
        $this->repository->method('findByUuid')->willReturn(null);

        $result = $this->service()->findByIdentifier(
            '922f91b4-cbe8-4ca0-8bf8-50de48f5d086',
            new Viewer(null, '127.0.0.1'),
        );

        self::assertTrue($result->isFailure());
        self::assertSame('POST_NOT_FOUND', $result->getError()->code);
    }

    private function service(): PostReadService
    {
        return new PostReadService(
            $this->repository,
            $this->loadEntityStats,
            $this->hashtagService,
            $this->incrementView,
        );
    }

    private function authenticatedViewer(): Viewer
    {
        return new Viewer(UserId::from(7), '127.0.0.1');
    }

    private function domainPost(int $id): Post
    {
        return new Post(
            id: $id,
            uuid: EntityId::from('922f91b4-cbe8-4ca0-8bf8-50de48f5d086'),
            title: "Post {$id}",
            content: 'content',
            topic: PostTopic::FAQ,
            locked: false,
            authorId: 7,
            authorUuid: EntityId::from('fa088bc4-1e1b-4f5c-9876-0bc3e99b058f'),
            authorName: 'Example user',
            createdAt: new DateTimeImmutable('2026-09-01T12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-09-01T12:30:00+00:00'),
        );
    }

    private function hashtag(int $id, string $content): object
    {
        return (object) ['id' => $id, 'content' => $content];
    }

    /**
     * @return array{page: int, per_page: int, total: int, last_page: int, has_more: bool}
     */
    private function pagination(int $total): array
    {
        return [
            'page' => 1,
            'per_page' => 5,
            'total' => $total,
            'last_page' => 1,
            'has_more' => false,
        ];
    }
}
