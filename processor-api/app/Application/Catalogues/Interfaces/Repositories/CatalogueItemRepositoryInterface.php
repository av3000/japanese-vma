<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Interfaces\Repositories;

interface CatalogueItemRepositoryInterface
{
    /**
     * @return int[]
     */
    public function findItemIdsByCatalogueId(int $catalogueId): array;

    /**
     * @param int[] $catalogueIds
     * @return array<int,int> map list_id => count
     */
    public function countItemsByCatalogueIds(array $catalogueIds): array;

    /**
     * @param int[] $itemIds
     * @return array<int,int> map real_object_id => count
     */
    public function countSavesByItemIds(array $itemIds, int $listType): array;
}
