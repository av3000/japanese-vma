<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Services;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Domain\Catalogues\DTOs\ViewerCatalogueStateDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\ValueObjects\EntityId;

final readonly class ViewerCatalogueStateService
{
    public function __construct(
        private CatalogueRepositoryInterface $catalogueRepository,
        private CatalogueItemRepositoryInterface $catalogueItemRepository,
    ) {
    }

    /**
     * @param  int[]  $itemIds
     * @return array<int, ViewerCatalogueStateDTO>
     */
    public function forItems(
        EntityId $ownerUuid,
        array $itemIds,
        SavedListType $savedType,
        ?SavedListType $knownType = null,
    ): array {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        if ($itemIds === []) {
            return [];
        }

        $types = [$savedType->value];

        if ($knownType !== null) {
            $types[] = $knownType->value;
        }

        $catalogues = $this->catalogueRepository->findOwnedForMembership(
            $ownerUuid->value(),
            null,
            array_values(array_unique($types)),
        );

        $catalogueIds = array_map(
            static fn (Catalogue $catalogue): int => $catalogue->getIdValue(),
            $catalogues,
        );

        $savedCatalogueIds = $this->catalogueIdsForType($catalogues, $savedType);
        $knownCatalogueIds = $knownType !== null ? $this->catalogueIdsForType($catalogues, $knownType) : [];

        if ($catalogueIds === []) {
            return $this->emptyStates($itemIds, $knownType !== null);
        }

        $membershipsByItemId = $this->catalogueItemRepository->findCatalogueIdsByItemIds($catalogueIds, $itemIds);
        $states = [];

        foreach ($itemIds as $itemId) {
            $containedCatalogueIds = $membershipsByItemId[$itemId] ?? [];

            $states[$itemId] = new ViewerCatalogueStateDTO(
                isSaved: $this->containsAny($containedCatalogueIds, $savedCatalogueIds),
                isKnown: $knownType !== null ? $this->containsAny($containedCatalogueIds, $knownCatalogueIds) : null,
            );
        }

        return $states;
    }

    /**
     * @param  Catalogue[]  $catalogues
     * @return int[]
     */
    private function catalogueIdsForType(array $catalogues, SavedListType $type): array
    {
        return array_values(array_map(
            static fn (Catalogue $catalogue): int => $catalogue->getIdValue(),
            array_filter(
                $catalogues,
                static fn (Catalogue $catalogue): bool => $catalogue->getType() === $type,
            ),
        ));
    }

    /**
     * @param  int[]  $containedCatalogueIds
     * @param  int[]  $targetCatalogueIds
     */
    private function containsAny(array $containedCatalogueIds, array $targetCatalogueIds): bool
    {
        return array_intersect($containedCatalogueIds, $targetCatalogueIds) !== [];
    }

    /**
     * @param  int[]  $itemIds
     * @return array<int, ViewerCatalogueStateDTO>
     */
    private function emptyStates(array $itemIds, bool $includeKnown): array
    {
        $states = [];

        foreach ($itemIds as $itemId) {
            $states[$itemId] = new ViewerCatalogueStateDTO(false, $includeKnown ? false : null);
        }

        return $states;
    }
}
