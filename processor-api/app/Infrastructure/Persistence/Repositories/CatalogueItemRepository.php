<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Domain\Shared\Enums\SavedListType;
use App\Http\Models\Kanji;
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

    public function containsItem(int $catalogueId, int $itemId): bool
    {
        return DB::table('customlist_object')
            ->where('list_id', $catalogueId)
            ->where('real_object_id', $itemId)
            ->exists();
    }

    public function addItem(int $catalogueId, SavedListType $catalogueType, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogueId,
            'listtype_id' => $catalogueType->value,
            'real_object_id' => $itemId,
        ]);

        $this->incrementLegacyKanjiJlptCounters($catalogueId, $catalogueType, $itemId);
    }

    public function removeItem(int $catalogueId, SavedListType $catalogueType, int $itemId): bool
    {
        $deletedRows = DB::table('customlist_object')
            ->where('list_id', $catalogueId)
            ->where('real_object_id', $itemId)
            ->delete();

        if ($deletedRows < 1) {
            return false;
        }

        $this->decrementLegacyKanjiJlptCounters($catalogueId, $catalogueType, $itemId);

        return true;
    }

    public function deleteByCatalogueId(int $catalogueId): void
    {
        DB::table('customlist_object')
            ->where('list_id', $catalogueId)
            ->delete();
    }

    private function incrementLegacyKanjiJlptCounters(int $catalogueId, SavedListType $catalogueType, int $itemId): void
    {
        if (! in_array($catalogueType, [SavedListType::KANJIS, SavedListType::KNOWNKANJIS], true)) {
            return;
        }

        $jlptLevel = Kanji::query()->whereKey($itemId)->value('jlpt');

        $column = match ((string) $jlptLevel) {
            '1' => 'n1',
            '2' => 'n2',
            '3' => 'n3',
            '4' => 'n4',
            '5' => 'n5',
            default => null,
        };

        if ($column === null) {
            return;
        }

        DB::table('customlists')
            ->where('id', $catalogueId)
            ->increment($column);
    }

    private function decrementLegacyKanjiJlptCounters(int $catalogueId, SavedListType $catalogueType, int $itemId): void
    {
        if (! in_array($catalogueType, [SavedListType::KANJIS, SavedListType::KNOWNKANJIS], true)) {
            return;
        }

        $jlptLevel = Kanji::query()->whereKey($itemId)->value('jlpt');

        $column = match ((string) $jlptLevel) {
            '1' => 'n1',
            '2' => 'n2',
            '3' => 'n3',
            '4' => 'n4',
            '5' => 'n5',
            default => null,
        };

        if ($column === null) {
            return;
        }

        DB::table('customlists')
            ->where('id', $catalogueId)
            ->decrement($column);
    }
}
