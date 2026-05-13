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
use App\Domain\Catalogues\DTOs\CataloguePickerResultDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\ValueObjects\CatalogueDescription;
use App\Domain\Catalogues\ValueObjects\CatalogueTitle;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Infrastructure\Persistence\Models\User;
use PHPUnit\Framework\TestCase;

class CatalogueServiceForItemTest extends TestCase
{
    public function test_catalogue_picker_result_pairs_each_catalogue_with_contains_item_state(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogueItemRepository = $this->createMock(CatalogueItemRepositoryInterface::class);

        $matchingCatalogue = $this->catalogue(10, 'Known Words', SavedListType::KNOWNWORDS);
        $otherCatalogue = $this->catalogue(20, 'Words To Review', SavedListType::WORDS);

        $catalogueRepository->expects($this->once())
            ->method('findOwnedForMembership')
            ->with('owner-uuid', null, [3, 7])
            ->willReturn([$matchingCatalogue, $otherCatalogue]);

        $catalogueItemRepository->expects($this->once())
            ->method('findCatalogueIdsContainingItem')
            ->with([10, 20], 321)
            ->willReturn([10]);

        $result = $this->service($catalogueRepository, $catalogueItemRepository)
            ->getCataloguesForItem(321, [3, 7], null, $this->user('owner-uuid'));

        $this->assertInstanceOf(CataloguePickerResultDTO::class, $result);
        $this->assertCount(2, $result->items);
        $this->assertSame($matchingCatalogue, $result->items[0]->catalogue);
        $this->assertTrue($result->items[0]->containsItem);
        $this->assertSame($otherCatalogue, $result->items[1]->catalogue);
        $this->assertFalse($result->items[1]->containsItem);
    }

    private function service(
        CatalogueRepositoryInterface $catalogueRepository,
        CatalogueItemRepositoryInterface $catalogueItemRepository,
    ): CatalogueService {
        return new CatalogueService(
            $catalogueRepository,
            $this->createMock(CataloguePolicy::class),
            $this->createMock(IncrementViewAction::class),
            $this->createMock(CatalogueItemService::class),
            $this->createMock(HashtagServiceInterface::class),
            $catalogueItemRepository,
            $this->createMock(HashtagRepositoryInterface::class),
            $this->createMock(ViewRepositoryInterface::class),
            $this->createMock(LikeRepositoryInterface::class),
            $this->createMock(DownloadRepositoryInterface::class),
            $this->createMock(CommentRepositoryInterface::class),
            $this->createMock(LoadEntityStatsAction::class),
            $this->createMock(EngagementServiceInterface::class),
        );
    }

    private function catalogue(int $id, string $title, SavedListType $type): Catalogue
    {
        return new Catalogue(
            $id,
            EntityId::generate(),
            $type,
            CatalogueTitle::fromInput($title),
            CatalogueDescription::empty(),
            PublicityStatus::PRIVATE,
            UserId::from(1),
            UserName::from('Owner User'),
            EntityId::generate(),
            new \DateTimeImmutable,
            new \DateTimeImmutable,
        );
    }

    private function user(string $uuid): User
    {
        $user = new User;
        $user->uuid = $uuid;

        return $user;
    }
}
