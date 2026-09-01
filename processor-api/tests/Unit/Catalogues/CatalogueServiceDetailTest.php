<?php

namespace Tests\Unit\Catalogues;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Policies\CataloguePolicy;
use App\Application\Catalogues\Services\CatalogueItemService;
use App\Application\Catalogues\Services\CatalogueService;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\ValueObjects\CatalogueDescription;
use App\Domain\Catalogues\ValueObjects\CatalogueTitle;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Shared\ValueObjects\Viewer;
use Tests\TestCase;

class CatalogueServiceDetailTest extends TestCase
{
    public function test_catalogue_detail_result_contains_enriched_response_data(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $cataloguePolicy = $this->createMock(CataloguePolicy::class);
        $incrementView = $this->createMock(IncrementViewAction::class);
        $catalogueItemService = $this->createMock(CatalogueItemService::class);
        $loadStats = $this->createMock(LoadEntityStatsAction::class);
        $hashtagService = $this->createMock(HashtagServiceInterface::class);
        $engagementService = $this->createMock(EngagementServiceInterface::class);

        $catalogue = $this->catalogue(10, 'Public Custom');
        $authenticatedUser = $this->authenticatedUser(42);
        $viewer = new Viewer($authenticatedUser->id, '127.0.0.1');
        $items = [['id' => 321, 'title' => 'Item']];
        $hashtags = [['id' => 1, 'content' => '#grammar']];

        $catalogueRepository->expects($this->once())
            ->method('findByPublicUid')
            ->with($catalogue->getUid())
            ->willReturn($catalogue);

        $cataloguePolicy->expects($this->once())
            ->method('canView')
            ->with($authenticatedUser, $catalogue)
            ->willReturn(true);

        $incrementView->expects($this->once())
            ->method('execute')
            ->with(10, ObjectTemplateType::LIST, $this->anything());

        $catalogueItemService->expects($this->once())
            ->method('getItems')
            ->with($catalogue)
            ->willReturn($items);

        $loadStats->expects($this->once())
            ->method('batchLoadStatsById')
            ->with(ObjectTemplateType::LIST->getLegacyId(), [10])
            ->willReturn([
                10 => [
                    'likes' => 4,
                    'downloads' => 5,
                    'views' => 6,
                    'comments' => 7,
                ],
            ]);

        $hashtagService->expects($this->once())
            ->method('getHashtags')
            ->with(10, ObjectTemplateType::LIST)
            ->willReturn($hashtags);

        $engagementService->expects($this->once())
            ->method('isEntityLikedByViewer')
            ->with(10, ObjectTemplateType::LIST, true)
            ->willReturn(true);

        $result = $this->service(
            $catalogueRepository,
            $cataloguePolicy,
            $incrementView,
            $catalogueItemService,
            $loadStats,
            $hashtagService,
            $engagementService,
        )->getCatalogueDetail($catalogue->getUid(), $viewer, $authenticatedUser);

        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(CatalogueDetailDTO::class, $result->getData());
        $this->assertSame($catalogue, $result->getData()->catalogue);
        $this->assertSame($items, $result->getData()->items);
        $this->assertSame(1, $result->getData()->itemsCount);
        $this->assertSame(4, $result->getData()->stats->getLikesCount());
        $this->assertSame(5, $result->getData()->stats->getDownloadsCount());
        $this->assertSame(6, $result->getData()->stats->getViewsCount());
        $this->assertSame(7, $result->getData()->stats->getCommentsCount());
        $this->assertSame($hashtags, $result->getData()->hashtags);
        $this->assertTrue($result->getData()->isLikedByViewer);
    }

    public function test_catalogue_detail_result_uses_zero_stats_when_stats_row_is_missing(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $cataloguePolicy = $this->createMock(CataloguePolicy::class);
        $incrementView = $this->createMock(IncrementViewAction::class);
        $catalogueItemService = $this->createMock(CatalogueItemService::class);
        $loadStats = $this->createMock(LoadEntityStatsAction::class);
        $hashtagService = $this->createMock(HashtagServiceInterface::class);
        $engagementService = $this->createMock(EngagementServiceInterface::class);

        $catalogue = $this->catalogue(10, 'Public Custom');

        $catalogueRepository->method('findByPublicUid')->willReturn($catalogue);
        $cataloguePolicy->method('canView')->willReturn(true);
        $incrementView->method('execute');
        $catalogueItemService->method('getItems')->willReturn([]);
        $loadStats->method('batchLoadStatsById')->willReturn([]);
        $hashtagService->method('getHashtags')->willReturn([]);
        $engagementService->method('isEntityLikedByViewer')->willReturn(false);

        $result = $this->service(
            $catalogueRepository,
            $cataloguePolicy,
            $incrementView,
            $catalogueItemService,
            $loadStats,
            $hashtagService,
            $engagementService,
        )->getCatalogueDetail($catalogue->getUid(), new Viewer(null, '127.0.0.1'));

        $this->assertTrue($result->isSuccess());
        $this->assertSame(0, $result->getData()->stats->getLikesCount());
        $this->assertSame(0, $result->getData()->stats->getDownloadsCount());
        $this->assertSame(0, $result->getData()->stats->getViewsCount());
        $this->assertSame(0, $result->getData()->stats->getCommentsCount());
        $this->assertSame([], $result->getData()->hashtags);
        $this->assertFalse($result->getData()->isLikedByViewer);
    }

    private function service(
        CatalogueRepositoryInterface $catalogueRepository,
        CataloguePolicy $cataloguePolicy,
        IncrementViewAction $incrementView,
        CatalogueItemService $catalogueItemService,
        LoadEntityStatsAction $loadStats,
        HashtagServiceInterface $hashtagService,
        EngagementServiceInterface $engagementService,
    ): CatalogueService {
        return new CatalogueService(
            $catalogueRepository,
            $cataloguePolicy,
            $incrementView,
            $catalogueItemService,
            $hashtagService,
            $this->createMock(CatalogueItemRepositoryInterface::class),
            $this->createMock(HashtagRepositoryInterface::class),
            $this->createMock(ViewRepositoryInterface::class),
            $this->createMock(LikeRepositoryInterface::class),
            $this->createMock(DownloadRepositoryInterface::class),
            $this->createMock(CommentRepositoryInterface::class),
            $loadStats,
            $engagementService,
        );
    }

    private function catalogue(int $id, string $title): Catalogue
    {
        return new Catalogue(
            $id,
            EntityId::generate(),
            SavedListType::RADICALS,
            CatalogueTitle::fromInput($title),
            CatalogueDescription::empty(),
            PublicityStatus::PUBLIC,
            UserId::from(1),
            UserName::from('Owner User'),
            EntityId::generate(),
            new \DateTimeImmutable,
            new \DateTimeImmutable,
        );
    }

    private function authenticatedUser(int $id): AuthenticatedUser
    {
        return new AuthenticatedUser(
            UserId::from($id),
            EntityId::generate(),
            UserName::from('Viewer User'),
            false,
        );
    }
}
