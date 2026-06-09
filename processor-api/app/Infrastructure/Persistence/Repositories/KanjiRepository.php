<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Domain\JapaneseMaterial\Kanjis\DTOs\KanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use Illuminate\Database\Eloquent\Builder;

class KanjiRepository implements KanjiRepositoryInterface
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper
    ) {
    }

    public function findByUuid(EntityId $uuid): ?DomainKanji
    {
        $persistenceKanji = PersistenceKanji::where('uuid', $uuid->value())->first();

        return $persistenceKanji ? $this->kanjiMapper->mapToDomain($persistenceKanji) : null;
    }

    public function findByCharacter(KanjiCharacter $character): ?DomainKanji
    {
        $persistenceKanji = PersistenceKanji::where('kanji', $character->value())->first();

        return $persistenceKanji ? $this->kanjiMapper->mapToDomain($persistenceKanji) : null;
    }

    public function find(?KanjiQueryCriteria $criteria = null): KanjiListResultDTO
    {
        $query = PersistenceKanji::query()->orderBy('id');

        if ($criteria) {
            $this->applyKanjiFilters($query, $criteria);
            $this->applyParentEntityFilters($query, $criteria);
        }

        $perPage = $criteria?->pagination?->per_page ?? Pagination::DEFAULT_PER_PAGE;
        $page = $criteria?->pagination?->page ?? Pagination::MIN_PAGE;

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()
            ->map(fn (PersistenceKanji $kanji): DomainKanji => $this->kanjiMapper->mapToDomain($kanji))
            ->all();

        return new KanjiListResultDTO(
            items: $items,
            pagination: [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        );
    }

    private function applyKanjiFilters(Builder $query, KanjiQueryCriteria $criteria): void
    {
        if ($criteria->uuid !== null) {
            $query->where('uuid', $criteria->uuid->value());
        }

        if ($criteria->keyword !== null && $criteria->keyword !== '') {
            $keyword = $criteria->keyword;

            $query->where(function (Builder $query) use ($keyword): void {
                $query->where('kanji', 'LIKE', "%{$keyword}%")
                    ->orWhere('meaning', 'LIKE', "%{$keyword}%");
            });
        }

        if ($criteria->character !== null) {
            $query->where('kanji', $criteria->character->value());
        }

        if ($criteria->grade !== null) {
            $query->byGrade($criteria->grade->value());
        }

        if ($criteria->jlpt !== null) {
            $query->byJlptLevel($criteria->jlpt->value());
        }

        if ($criteria->minStrokeCount !== null) {
            $query->whereRaw('CAST(stroke_count AS UNSIGNED) >= ?', [$criteria->minStrokeCount]);
        }

        if ($criteria->maxStrokeCount !== null) {
            $query->whereRaw('CAST(stroke_count AS UNSIGNED) <= ?', [$criteria->maxStrokeCount]);
        }

        if ($criteria->meanings !== null && $criteria->meanings !== []) {
            $query->where(function (Builder $query) use ($criteria): void {
                foreach ($criteria->meanings as $meaning) {
                    $query->orWhere('meaning', 'LIKE', "%{$meaning}%");
                }
            });
        }

        if ($criteria->onyomi !== null && $criteria->onyomi !== []) {
            $query->where(function (Builder $query) use ($criteria): void {
                foreach ($criteria->onyomi as $onyomi) {
                    $query->orWhere('onyomi', 'LIKE', "%{$onyomi}%");
                }
            });
        }

        if ($criteria->kunyomi !== null && $criteria->kunyomi !== []) {
            $query->where(function (Builder $query) use ($criteria): void {
                foreach ($criteria->kunyomi as $kunyomi) {
                    $query->orWhere('kunyomi', 'LIKE', "%{$kunyomi}%");
                }
            });
        }

        if ($criteria->radical !== null) {
            $query->where('radicals', 'LIKE', "%{$criteria->radical}%");
        }
    }

    private function applyParentEntityFilters(Builder $query, KanjiQueryCriteria $criteria): void
    {
        if ($criteria->articleId !== null) {
            $query->whereHas('articles', function (Builder $q) use ($criteria) {
                $q->where('uuid', $criteria->articleId->value());
            });
            // Alternative: use a join for performance on large pivot tables
            // $query->join('article_kanji', 'japanese_kanji_bank_long.id', '=', 'article_kanji.kanji_id')
            //       ->join('articles', 'article_kanji.article_id', '=', 'articles.id')
            //       ->where('articles.uuid', $criteria->articleId->value())
            //       ->select('japanese_kanji_bank_long.*'); // Important to select original kanji columns
        }
        // TODO: implement when customLists will be migrated to clean architecture
        // if ($criteria->customListId !== null) {
        //     // Similarly, query through the custom_list_kanji pivot table
        //     $query->whereHas('customLists', function(Builder $q) use ($criteria) {
        //         $q->where('id', $criteria->customListId);
        //     });
        // }
    }

    public function findIdsByCharacters(array $characters): array
    {
        return PersistenceKanji::whereIn('kanji', $characters)
            ->pluck('id')
            ->toArray();
    }

    public function findByIds(array $ids): array
    {
        $entities = PersistenceKanji::whereIn('id', $ids)->get();

        // TODO: create and use kanji mapper
        // TODO: create and use kanji domain object with value objects
        return $entities;
    }

    public function findByCharacters(array $characters): array
    {
        $entities = PersistenceKanji::whereIn('kanji', $characters)->get();

        // TODO: create and use kanji mapper
        // TODO: create and use kanji domain object with value objects
        return $entities;
    }

    public function findManyByCharacters(array $characters): array
    {
        if (empty($characters)) {
            return [];
        }

        $charValues = array_map(fn (KanjiCharacter $c) => $c->value(), $characters);

        $persistenceKanjis = PersistenceKanji::whereIn('kanji', $charValues)->get();

        return $persistenceKanjis->map(
            fn (PersistenceKanji $pk) => $this->kanjiMapper->mapToDomain($pk)
        )->toArray();
    }
}
