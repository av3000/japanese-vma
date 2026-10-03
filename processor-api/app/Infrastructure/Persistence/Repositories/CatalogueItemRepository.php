<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Domain\Catalogues\Enums\CatalogueJlptSource;
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
            ->select('list_id', DB::raw('count(*) as count'))
            ->pluck('count', 'list_id')
            ->toArray();
    }

    public function countJlptLevelsByCatalogueIds(CatalogueJlptSource $source, array $catalogueIds): array
    {
        if (empty($catalogueIds)) {
            return [];
        }

        // Distinct (catalogue, entry) pairs first: a word in two sentences, a kanji in two articles,
        // or an item saved twice is one entry. Deduplicating before the join lets PostgreSQL hash
        // the pairs; count(DISTINCT ...) sorted every row and was about 3x slower on 500-item
        // Articles catalogues (#386).
        [$entryColumn, $entryTable] = match ($source) {
            CatalogueJlptSource::KANJI => ['item.real_object_id', 'japanese_kanji_bank_long'],
            CatalogueJlptSource::WORDS => ['item.real_object_id', 'japanese_word_bank_long'],
            CatalogueJlptSource::SENTENCE_WORDS => ['sentence_word.word_id', 'japanese_word_bank_long'],
            CatalogueJlptSource::ARTICLE_KANJI => ['article_kanji.kanji_id', 'japanese_kanji_bank_long'],
        };

        $pairs = DB::table('customlist_object as item')
            ->whereIn('item.list_id', $catalogueIds)
            ->select('item.list_id', "{$entryColumn} as entry_id")
            ->distinct();

        if ($source === CatalogueJlptSource::SENTENCE_WORDS) {
            $pairs->join('japanese_sentence_word as sentence_word', 'sentence_word.sentence_id', '=', 'item.real_object_id');
        }

        if ($source === CatalogueJlptSource::ARTICLE_KANJI) {
            $pairs->join('article_kanji', 'article_kanji.article_id', '=', 'item.real_object_id');
        }

        $rows = DB::query()
            ->fromSub($pairs, 'pair')
            ->join("{$entryTable} as entry", 'entry.id', '=', 'pair.entry_id')
            ->groupBy('pair.list_id', 'entry.jlpt')
            ->select('pair.list_id', 'entry.jlpt', DB::raw('count(*) as count'))
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->list_id][(string) $row->jlpt] = (int) $row->count;
        }

        return $map;
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
            ->select('real_object_id', DB::raw('count(*) as count'))
            ->pluck('count', 'real_object_id')
            ->toArray();
    }

    public function findCatalogueIdsContainingItem(array $catalogueIds, int $itemId): array
    {
        if (empty($catalogueIds)) {
            return [];
        }

        return DB::table('customlist_object')
            ->whereIn('list_id', $catalogueIds)
            ->where('real_object_id', $itemId)
            ->pluck('list_id')
            ->toArray();
    }

    public function findCatalogueIdsByItemIds(array $catalogueIds, array $itemIds): array
    {
        if (empty($catalogueIds) || empty($itemIds)) {
            return [];
        }

        $rows = DB::table('customlist_object')
            ->select(['real_object_id', 'list_id'])
            ->whereIn('list_id', $catalogueIds)
            ->whereIn('real_object_id', $itemIds)
            ->get();

        $map = [];

        foreach ($rows as $row) {
            $itemId = (int) $row->real_object_id;
            $map[$itemId] ??= [];
            $map[$itemId][] = (int) $row->list_id;
        }

        return $map;
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

    public function deleteByItem(int $itemId, array $catalogueTypes): void
    {
        DB::table('customlist_object')
            ->where('real_object_id', $itemId)
            ->whereIn(
                'listtype_id',
                array_map(
                    static fn (SavedListType $catalogueType): int => $catalogueType->value,
                    $catalogueTypes,
                ),
            )
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
