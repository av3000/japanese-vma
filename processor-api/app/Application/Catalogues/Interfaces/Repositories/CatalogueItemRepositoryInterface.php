<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Interfaces\Repositories;

use App\Domain\Catalogues\Enums\CatalogueJlptSource;
use App\Domain\Shared\Enums\SavedListType;

interface CatalogueItemRepositoryInterface
{
    /**
     * @return int[]
     */
    public function findItemIdsByCatalogueId(int $catalogueId): array;

    /**
     * @param int[] $catalogueIds
     *
     * @return array<int,int> map list_id => count
     */
    public function countItemsByCatalogueIds(array $catalogueIds): array;

    /**
     * One grouped query for every given catalogue, whatever their number. Each distinct
     * kanji or word is counted once per catalogue, under its raw `jlpt` value.
     *
     * @param int[] $catalogueIds catalogues whose type has this source
     *
     * @return array<int, array<string, int>> map list_id => raw jlpt value => count
     */
    public function countJlptLevelsByCatalogueIds(CatalogueJlptSource $source, array $catalogueIds): array;

    /**
     * @param int[] $itemIds
     *
     * @return array<int,int> map real_object_id => count
     */
    public function countSavesByItemIds(array $itemIds, int $listType): array;

    /**
     * @param int[] $catalogueIds
     *
     * @return int[]
     */
    public function findCatalogueIdsContainingItem(array $catalogueIds, int $itemId): array;

    /**
     * @param int[] $catalogueIds
     * @param int[] $itemIds
     *
     * @return array<int, int[]> map real_object_id => list_id[]
     */
    public function findCatalogueIdsByItemIds(array $catalogueIds, array $itemIds): array;

    public function containsItem(int $catalogueId, int $itemId): bool;

    public function addItem(int $catalogueId, SavedListType $catalogueType, int $itemId): void;

    public function removeItem(int $catalogueId, SavedListType $catalogueType, int $itemId): bool;

    public function deleteByCatalogueId(int $catalogueId): void;

    /**
     * @param array<int, SavedListType> $catalogueTypes
     */
    public function deleteByItem(int $itemId, array $catalogueTypes): void;
}
