<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\JapaneseMaterial\Kanjis\Interfaces\Repositories\KanjiRepositoryInterface;
use App\Infrastructure\Persistence\Models\Kanji as PersistenceKanji;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji as DomainKanji;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanjis;
use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiCharacter;
use App\Domain\JapaneseMaterial\Kanjis\ValueObjects\KanjiSortCriteria;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use Illuminate\Database\Eloquent\Builder;

class KanjiRepository implements KanjiRepositoryInterface
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper
    ) {}

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

    public function find(?KanjiQueryCriteria $criteria = null): Kanjis
    {
        $query = PersistenceKanji::query();

        if ($criteria) {
            $this->applyFilters($query, $criteria);
        } else {
            $query->orderBy('kanji', 'asc');
        }

        $perPage = $criteria?->pagination?->per_page ?? Pagination::DEFAULT_PER_PAGE;
        $page = $criteria?->pagination?->page ?? Pagination::MIN_PAGE;

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginatedResults */
        $paginatedResults = $query->paginate($perPage, ['*'], 'page', $page);

        $domainKanjis = $paginatedResults->getCollection()
            ->map(function (PersistenceKanji $persistenceKanji) {
                return $this->kanjiMapper->mapToDomain($persistenceKanji);
            });

        $paginatedResults->setCollection($domainKanjis);

        return Kanjis::fromEloquentPaginator($paginatedResults);
    }

    private function applyFilters(Builder $query, KanjiQueryCriteria $criteria): void
    {
        if ($criteria->uuid !== null) {
            $query->where('uuid', $criteria->uuid->value());
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
            $query->where('stroke_count', '>=', $criteria->minStrokeCount['min']);
        }

        if ($criteria->maxStrokeCount !== null) {
            $query->where('stroke_count', '<=', $criteria->maxStrokeCount['max']);
        }

        if ($criteria->meanings !== null && !empty($criteria->meanings)) {
            foreach ($criteria->meanings as $meaning) {
                $query->where('meaning', 'LIKE', '%' . $meaning . '%');
            }
        }
        if ($criteria->onyomi !== null && !empty($criteria->onyomi)) {
            foreach ($criteria->onyomi as $onyomi) {
                $query->orWhere('onyomi', 'LIKE', '%' . $onyomi . '%');
            }
        }
        if ($criteria->kunyomi !== null && !empty($criteria->kunyomi)) {
            foreach ($criteria->kunyomi as $kunyomi) {
                $query->orWhere('kunyomi', 'LIKE', '%' . $kunyomi . '%');
            }
        }
        // TODO: Radical and kanji have relational table, use that to find by radicals
        if ($criteria->radical !== null) {
            $query->where('radicals', 'LIKE', '%' . $criteria->radical . '%');
            // Or use a relation if 'radicals' is a proper relation
            // $query->whereHas('radicals', fn($q) => $q->where('character', $criteria->radical));
        }
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
}
