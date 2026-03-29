<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CatalogueItemRepository implements CatalogueItemRepositoryInterface
{
    public function findItemIdsByCatalogueId(int $catalogueId): array
    {
        return DB::table('customlist_object')
            ->where('list_id', $catalogueId)
            ->pluck('real_object_id')
            ->toArray();
    }

    public function countItemsByCatalogueIds(array $catalogueIds): array
    {
        if (empty($catalogueIds)) {
            return [];
        }

        return DB::table('customlist_object')
            ->whereIn('list_id', $catalogueIds)
            ->groupBy('list_id')
            ->pluck(DB::raw('count(*)'), 'list_id')
            ->toArray();
    }

    public function countSavesByItemIds(array $itemIds, int $listType): array
    {
        if (empty($itemIds)) {
            return [];
        }

        return DB::table('customlist_object')
            ->whereIn('real_object_id', $itemIds)
            ->where('listtype_id', $listType)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();
    }
}
