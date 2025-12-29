<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Builders;

use App\Domain\JapaneseMaterial\Kanjis\Queries\KanjiQueryCriteria;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Infrastructure\Persistence\Repositories\KanjiMapper;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanjis as DomainKanjisCollection;
use App\Domain\Shared\ValueObjects\Pagination;

class KanjiRelationQueryBuilder
{
    public function __construct(
        private readonly KanjiMapper $kanjiMapper
    ) {}

    /**
     * Applies filters and sorting from KanjiQueryCriteria to an Eloquent Relation query.
     *
     * @param Relation $relation The Eloquent relationship builder (e.g., $article->kanjis())
     * @param KanjiQueryCriteria $criteria Criteria for filtering, sorting, and pagination
     * @return \Illuminate\Database\Eloquent\Builder The modified Eloquent query builder for Kanjis
     */
    public function build(Relation $relation, KanjiQueryCriteria $criteria): \Illuminate\Database\Eloquent\Builder
    {
        $query = $relation->getQuery(); // Get the underlying Eloquent Builder

        $this->applyFilters($query, $criteria);

        // TODO: has known bug with unrecognizing the encoding of kanji and returning ??? instead of actual value
        // $this->applySorting($query, $criteria->sort);

        return $query;
    }

    /**
     * Executes the query and returns a paginated DomainKanjisCollection.
     *
     * @param Relation $relation
     * @param KanjiQueryCriteria $criteria
     * @return DomainKanjisCollection
     */
    public function getPaginatedKanjis(Relation $relation, KanjiQueryCriteria $criteria): DomainKanjisCollection
    {
        $queryBuilder = $this->build($relation, $criteria);

        $perPage = $criteria->pagination?->per_page ?? Pagination::DEFAULT_PER_PAGE;
        $page = $criteria->pagination?->page ?? Pagination::MIN_PAGE;

        $paginatedResults = $queryBuilder->paginate(
            perPage: $perPage,
            page: $page
        );

        $domainKanjis = $paginatedResults->getCollection()->map(
            fn($persistenceKanji) => $this->kanjiMapper->mapToDomain($persistenceKanji)
        );

        $paginatedResults->setCollection($domainKanjis);

        return DomainKanjisCollection::fromEloquentPaginator($paginatedResults);
    }


    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query, KanjiQueryCriteria $criteria): void
    {
        if ($criteria->character !== null) {
            $query->where('kanji', $criteria->character->value());
        }
        if ($criteria->grade !== null) {
            $query->where('grade', $criteria->grade->value());
        }
        if ($criteria->jlpt !== null) {
            $query->where('jlpt', $criteria->jlpt->value());
        }
        if ($criteria->minStrokeCount !== null) {
            $query->where('stroke_count', '>=', $criteria->minStrokeCount);
        }
        if ($criteria->maxStrokeCount !== null) {
            $query->where('stroke_count', '<=', $criteria->maxStrokeCount);
        }
        if ($criteria->meanings !== null && !empty($criteria->meanings)) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria->meanings as $meaning) {
                    $q->orWhere('meaning', 'LIKE', '%' . $meaning . '%');
                }
            });
        }
        if ($criteria->onyomi !== null && !empty($criteria->onyomi)) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria->onyomi as $onyomi) {
                    $q->orWhere('onyomi', 'LIKE', '%' . $onyomi . '%');
                }
            });
        }
        if ($criteria->kunyomi !== null && !empty($criteria->kunyomi)) {
            $query->where(function ($q) use ($criteria) {
                foreach ($criteria->kunyomi as $kunyomi) {
                    $q->orWhere('kunyomi', 'LIKE', '%' . $kunyomi . '%');
                }
            });
        }
        if ($criteria->radical !== null) {
            $query->where('radicals', 'LIKE', '%' . $criteria->radical . '%');
        }
    }

    // TODO: Fix encoding issue to have sorting. Application of sort makes kanji values '????'
    // private function applySorting(\Illuminate\Database\Eloquent\Builder $query, ?KanjiSortCriteria $sort): void
    // {
    //     if ($sort !== null) {
    //         $query->orderBy($sort->field->value, $sort->direction->value);
    //     }
    // }
}
