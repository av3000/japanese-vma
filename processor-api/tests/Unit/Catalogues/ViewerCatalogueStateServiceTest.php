<?php

declare(strict_types=1);

namespace Tests\Unit\Catalogues;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Catalogues\Services\ViewerCatalogueStateService;
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

final class ViewerCatalogueStateServiceTest extends TestCase
{
    public function test_it_resolves_saved_and_known_state_for_many_items_in_one_batch(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogueItemRepository = $this->createMock(CatalogueItemRepositoryInterface::class);

        $knownWords = $this->catalogue(10, 'Known Words', SavedListType::KNOWNWORDS);
        $savedWords = $this->catalogue(20, 'Words To Review', SavedListType::WORDS);

        $catalogueRepository->expects($this->once())
            ->method('findOwnedForMembership')
            ->with('owner-uuid', null, [7, 3])
            ->willReturn([$knownWords, $savedWords]);

        $catalogueItemRepository->expects($this->once())
            ->method('findCatalogueIdsByItemIds')
            ->with([10, 20], [101, 102, 103])
            ->willReturn([
                101 => [20],
                102 => [10],
                103 => [10, 20],
            ]);

        $states = (new ViewerCatalogueStateService($catalogueRepository, $catalogueItemRepository))
            ->forItems(
                user: $this->user('owner-uuid'),
                itemIds: [101, 102, 103],
                savedType: SavedListType::WORDS,
                knownType: SavedListType::KNOWNWORDS,
            );

        $this->assertTrue($states[101]->isSaved);
        $this->assertFalse($states[101]->isKnown);
        $this->assertFalse($states[102]->isSaved);
        $this->assertTrue($states[102]->isKnown);
        $this->assertTrue($states[103]->isSaved);
        $this->assertTrue($states[103]->isKnown);
    }

    public function test_it_returns_false_state_for_items_with_no_membership(): void
    {
        $catalogueRepository = $this->createMock(CatalogueRepositoryInterface::class);
        $catalogueItemRepository = $this->createMock(CatalogueItemRepositoryInterface::class);

        $catalogueRepository->method('findOwnedForMembership')->willReturn([]);
        $catalogueItemRepository->expects($this->never())->method('findCatalogueIdsByItemIds');

        $states = (new ViewerCatalogueStateService($catalogueRepository, $catalogueItemRepository))
            ->forItems(
                user: $this->user('owner-uuid'),
                itemIds: [101],
                savedType: SavedListType::WORDS,
                knownType: SavedListType::KNOWNWORDS,
            );

        $this->assertFalse($states[101]->isSaved);
        $this->assertFalse($states[101]->isKnown);
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
