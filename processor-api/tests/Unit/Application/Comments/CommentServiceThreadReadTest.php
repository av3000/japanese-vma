<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Comments;

use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Comments\Policies\CommentPolicy;
use App\Application\Comments\Services\CommentEntityResolver;
use App\Application\Comments\Services\CommentService;
use App\Domain\Comments\DTOs\CommentListIncludes;
use App\Domain\Comments\DTOs\CommentListResultDTO;
use App\Domain\Comments\DTOs\CommentPageDTO;
use App\Domain\Comments\DTOs\CommentPaginationDTO;
use App\Domain\Comments\DTOs\CommentRepliesPreviewDTO;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Queries\CommentQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Shared\Results\Result;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The list use case against a mocked reader.
 *
 * The behaviour worth pinning here is the pairing: each root must be handed its
 * own subtree size and its own previews. Nothing in the types stops the wrong
 * count landing on the wrong comment, so a test has to.
 */
class CommentServiceThreadReadTest extends TestCase
{
    private const ENTITY_ID = 77;

    public function test_each_root_receives_its_own_count_and_previews(): void
    {
        $quiet = $this->comment(id: 1);
        $busy = $this->comment(id: 2);
        $childless = $this->comment(id: 3);

        $busyReply = $this->comment(id: 20, parentCommentId: 2);

        $reader = $this->readerReturning(
            page: $this->pageOf([$quiet, $busy, $childless]),
            previews: [
                1 => new CommentRepliesPreviewDTO(count: 2, replies: [$this->comment(id: 10, parentCommentId: 1)]),
                2 => new CommentRepliesPreviewDTO(count: 40, replies: [$busyReply]),
                // 3 is absent: roots with no replies are not returned.
            ],
        );

        $result = $this->service($reader)->getCommentsForEntity(
            entityType: ObjectTemplateType::POST,
            entityUuid: EntityId::from($this->uuid()),
            criteria: CommentQueryCriteria::default(),
            includes: new CommentListIncludes(includeReplies: true),
            viewer: null,
        );

        /** @var CommentListResultDTO $data */
        $data = $result->getData();

        $this->assertCount(3, $data->items);

        $this->assertSame(1, $data->items[0]->comment->getIdValue());
        $this->assertSame(2, $data->items[0]->repliesCount);
        $this->assertCount(1, $data->items[0]->replyPreviews);
        $this->assertSame(10, $data->items[0]->replyPreviews[0]->getIdValue());

        $this->assertSame(2, $data->items[1]->comment->getIdValue());
        $this->assertSame(40, $data->items[1]->repliesCount, 'The busy root must keep its own subtree size');
        $this->assertSame(20, $data->items[1]->replyPreviews[0]->getIdValue());

        $this->assertSame(3, $data->items[2]->comment->getIdValue());
        $this->assertSame(0, $data->items[2]->repliesCount, 'A root the reader omitted has no replies, not a borrowed count');
        $this->assertSame([], $data->items[2]->replyPreviews);
    }

    /**
     * A root with forty replies and none loaded is a normal response, not a
     * truncation bug, so the count must survive a counts-only read.
     */
    public function test_counts_are_attached_even_when_previews_were_not_requested(): void
    {
        $root = $this->comment(id: 5);

        $reader = $this->createMock(CommentThreadReaderInterface::class);
        $reader->method('rootPage')->willReturn($this->pageOf([$root]));
        $reader->expects($this->once())
            ->method('replyPreviews')
            ->with([5], 0, null)
            ->willReturn([5 => new CommentRepliesPreviewDTO(count: 40, replies: [])]);

        $result = $this->service($reader)->getCommentsForEntity(
            entityType: ObjectTemplateType::POST,
            entityUuid: EntityId::from($this->uuid()),
            criteria: CommentQueryCriteria::default(),
            includes: CommentListIncludes::countsOnly(),
            viewer: null,
        );

        /** @var CommentListResultDTO $data */
        $data = $result->getData();

        $this->assertSame(40, $data->items[0]->repliesCount);
        $this->assertSame([], $data->items[0]->replyPreviews);
    }

    public function test_the_requested_preview_limit_reaches_the_reader(): void
    {
        $reader = $this->createMock(CommentThreadReaderInterface::class);
        $reader->method('rootPage')->willReturn($this->pageOf([$this->comment(id: 9)]));
        $reader->expects($this->once())
            ->method('replyPreviews')
            ->with([9], 7, null)
            ->willReturn([]);

        $this->service($reader)->getCommentsForEntity(
            entityType: ObjectTemplateType::POST,
            entityUuid: EntityId::from($this->uuid()),
            criteria: CommentQueryCriteria::default(),
            includes: new CommentListIncludes(includeReplies: true, repliesLimit: 7),
            viewer: null,
        );
    }

    /**
     * An empty page must not cost a second query.
     */
    public function test_an_empty_page_does_not_ask_for_previews(): void
    {
        $reader = $this->createMock(CommentThreadReaderInterface::class);
        $reader->method('rootPage')->willReturn($this->pageOf([]));
        $reader->expects($this->never())->method('replyPreviews');

        $result = $this->service($reader)->getCommentsForEntity(
            entityType: ObjectTemplateType::POST,
            entityUuid: EntityId::from($this->uuid()),
            criteria: CommentQueryCriteria::default(),
            includes: new CommentListIncludes(includeReplies: true),
            viewer: null,
        );

        /** @var CommentListResultDTO $data */
        $data = $result->getData();

        $this->assertSame([], $data->items);
    }

    public function test_pagination_is_passed_through_untouched(): void
    {
        $reader = $this->readerReturning($this->pageOf([]), []);

        $result = $this->service($reader)->getCommentsForEntity(
            entityType: ObjectTemplateType::POST,
            entityUuid: EntityId::from($this->uuid()),
            criteria: CommentQueryCriteria::default(),
            includes: CommentListIncludes::countsOnly(),
            viewer: null,
        );

        /** @var CommentListResultDTO $data */
        $data = $result->getData();

        $this->assertSame(1, $data->pagination->page);
        $this->assertSame(20, $data->pagination->perPage);
        $this->assertSame(0, $data->pagination->total);
        $this->assertFalse($data->pagination->hasMore);
    }

    /**
     * @param array<int, CommentRepliesPreviewDTO> $previews
     */
    private function readerReturning(CommentPageDTO $page, array $previews): CommentThreadReaderInterface
    {
        $reader = $this->createMock(CommentThreadReaderInterface::class);
        $reader->method('rootPage')->willReturn($page);
        $reader->method('replyPreviews')->willReturn($previews);

        return $reader;
    }

    private function service(CommentThreadReaderInterface $reader): CommentService
    {
        $resolver = $this->createMock(CommentEntityResolver::class);
        $resolver->method('resolveByUuid')->willReturn(Result::success(self::ENTITY_ID));

        return new CommentService(
            $this->createMock(CommentRepositoryInterface::class),
            $reader,
            $resolver,
            new CommentPolicy,
        );
    }

    /**
     * @param array<int, Comment> $comments
     */
    private function pageOf(array $comments): CommentPageDTO
    {
        return new CommentPageDTO(
            comments: $comments,
            pagination: new CommentPaginationDTO(
                page: 1,
                perPage: 20,
                total: count($comments),
                lastPage: 1,
                hasMore: false,
            ),
        );
    }

    private function comment(int $id, ?int $parentCommentId = null): Comment
    {
        return new Comment(
            $id,
            EntityId::from($this->uuid()),
            self::ENTITY_ID,
            EntityId::from($this->uuid()),
            ObjectTemplateType::POST,
            'Author',
            EntityId::from($this->uuid()),
            new UserId(1),
            "Comment {$id}",
            $parentCommentId,
            0,
            false,
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
            new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
        );
    }

    private function uuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-4%03x-%04x-%04x%04x%04x',
            random_int(0, 0xFFFF), random_int(0, 0xFFFF),
            random_int(0, 0xFFFF),
            random_int(0, 0x0FFF),
            random_int(0, 0x3FFF) | 0x8000,
            random_int(0, 0xFFFF), random_int(0, 0xFFFF), random_int(0, 0xFFFF),
        );
    }
}
