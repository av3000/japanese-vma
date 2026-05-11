<?php

namespace Tests\Unit\Catalogues;

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
use App\Domain\Catalogues\DTOs\CatalogueListDTO;
use App\Domain\Catalogues\DTOs\CatalogueListResultDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\Catalogues;
use App\Domain\Catalogues\ValueObjects\CatalogueDescription;
use App\Domain\Catalogues\ValueObjects\CatalogueTitle;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class CatalogueServiceListTest extends TestCase
{
    public function test_catalogue_list_result_contains_enriched_items_and_pagination(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogueItemRepository = $this->createMock(CatalogueItemRepositoryInterface::class);
        $loadStats = $this->createMock(LoadEntityStatsAction::class);
        $hashtagService = $this->createMock(HashtagServiceInterface::class);

        $catalogue = $this->catalogue(10, 'Public Custom');
        $catalogues = Catalogues::fromArray(
            [$catalogue],
            new LengthAwarePaginator([$catalogue], 1, 20, 1),
        );

        $catalogueRepository->expects($this->once())
            ->method('findByCriteria')
            ->willReturn($catalogues);

        $catalogueItemRepository->expects($this->once())
            ->method('countItemsByCatalogueIds')
            ->with([10])
            ->willReturn([10 => 3]);

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
            ->method('getBatchHashtags')
            ->with([10], ObjectTemplateType::LIST)
            ->willReturn([
                10 => [
                    ['id' => 1, 'content' => 'grammar'],
                ],
            ]);

        $result = $this->service($catalogueRepository, $catalogueItemRepository, $loadStats, $hashtagService)
            ->getCatalogueList($this->listDto(includeStats: true, includeHashtags: true));

        $this->assertInstanceOf(CatalogueListResultDTO::class, $result);
        $this->assertCount(1, $result->items);
        $this->assertSame($catalogue, $result->items[0]->catalogue);
        $this->assertSame(3, $result->items[0]->itemsCount);
        $this->assertSame(4, $result->items[0]->stats?->getLikesCount());
        $this->assertSame([['id' => 1, 'content' => 'grammar']], $result->items[0]->hashtags);
        $this->assertSame(1, $result->pagination['page']);
        $this->assertSame(20, $result->pagination['per_page']);
        $this->assertSame(1, $result->pagination['total']);
    }

    private function service(
        CatalogueRepositoryInterface $catalogueRepository,
        CatalogueItemRepositoryInterface $catalogueItemRepository,
        LoadEntityStatsAction $loadStats,
        HashtagServiceInterface $hashtagService,
    ): CatalogueService {
        return new CatalogueService(
            $catalogueRepository,
            $this->createMock(CataloguePolicy::class),
            $this->createMock(IncrementViewAction::class),
            $this->createMock(CatalogueItemService::class),
            $hashtagService,
            $catalogueItemRepository,
            $this->createMock(HashtagRepositoryInterface::class),
            $this->createMock(ViewRepositoryInterface::class),
            $this->createMock(LikeRepositoryInterface::class),
            $this->createMock(DownloadRepositoryInterface::class),
            $this->createMock(CommentRepositoryInterface::class),
            $loadStats,
            $this->createMock(EngagementServiceInterface::class),
        );
    }

    private function listDto(bool $includeStats, bool $includeHashtags): CatalogueListDTO
    {
        return new CatalogueListDTO(
            search: null,
            owner_uid: null,
            type: null,
            sort_by: null,
            sort_dir: null,
            per_page: null,
            page: null,
            include_stats_counts: $includeStats,
            include_hashtags: $includeHashtags,
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
}
