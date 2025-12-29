<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use App\Domain\Articles\DTOs\{ArticleCriteriaDTO, ArticleIncludeOptionsDTO};
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\ValueObjects\{UserId, EntityId};
use App\Domain\Shared\Enums\PublicityStatus;
// use App\Infrastructure\Persistence\Builders\KanjiRelationQueryBuilder;
use Illuminate\Database\Eloquent\Builder;

class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private readonly ArticleMapper $articleMapper,
        // private readonly KanjiRelationQueryBuilder $kanjiRelationQueryBuilder
    ) {}

    /**
     * Create a new article in persistence.
     *
     * @param DomainArticle $article The domain article to create
     * @return DomainArticle The created article with generated ID and relationships
     * @throws \Illuminate\Database\QueryException On database constraint violation
     */
    public function create(DomainArticle $article): DomainArticle
    {
        // TODO: use class::method if needed ArticleMapper::mapToEntity($article);
        $mappedArticle = $this->articleMapper->mapToEntity($article);
        $entityArticle = PersistenceArticle::create($mappedArticle);
        $entityArticle->load('user');

        return $this->articleMapper->mapToDomain($entityArticle);
    }

    /**
     * Update an existing article in persistence.
     *
     * @param DomainArticle $article The domain article with updated state
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article doesn't exist
     */
    public function update(DomainArticle $article): void
    {
        $entityArticle = PersistenceArticle::with('user')
            ->where('uuid', $article->getUid()->value())
            ->firstOrFail();

        $this->articleMapper->mapToExistingEntity($article, $entityArticle);
        $entityArticle->save();

        // TODO: update attached kanjis
    }

    /**
     * Find article by public UUID with optional selective eager loading.
     *
     *
     * @param EntityId $articleUuid The article's public UUID
     * @param ArticleIncludeOptionsDTO|null $dto Options for eager loading:
     * @return DomainArticle|null The domain article if found, null if not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByPublicUid(EntityId $articleUuid, ?ArticleIncludeOptionsDTO $dto = null): ?DomainArticle
    {
        $query = PersistenceArticle::query();

        $persistenceArticle = $query->with(['user', 'kanjis'])
            ->where('uuid', $articleUuid->value())
            ->first();

        return $persistenceArticle ? $this->articleMapper->mapToDomain($persistenceArticle) : null;
    }

    /**
     * Delete article by integer ID with proper relationship cleanup.
     * Note: Engagement data (likes, views, comments) should be cleaned up
     * by the service layer before calling this method.
     *
     * @param int $id The article's integer ID (not UUID)
     * @return bool True if deleted successfully
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If article with ID not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function deleteById(int $id): bool
    {
        $persistenceArticle = PersistenceArticle::findOrFail($id);

        $persistenceArticle->kanjis()->detach();
        $persistenceArticle->words()->detach();

        return $persistenceArticle->delete();
    }

    /**
     * Find articles by author user ID with limit.
     *
     * Returns most recent articles by a specific user, ordered by creation date descending.
     * Eager loads user and kanjis relationships to avoid N+1 queries.
     *
     * @param UserId $userId The author's user ID
     * @param int $limit Maximum number of articles to return (default: 10)
     * @return array<array> Array of article arrays (raw Eloquent toArray() output, not domain models)
     * @throws \Illuminate\Database\QueryException On database failure
     * @deprecated Consider using findByCriteria() instead for consistent return types
     * @todo This returns raw arrays instead of domain models - inconsistent with other methods
     */
    public function findByUserId(UserId $userId, int $limit = 10): array
    {
        return PersistenceArticle::where('user_id', $userId->value())
            ->with(['user', 'kanjis'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get integer ID from article UUID.
     *
     * Performs a lightweight query returning only the ID column.
     * Useful when you need the integer ID for operations but only have the public UUID.
     *
     * @param EntityId $entityUuid The article's public UUID
     * @return int|null The article's integer ID, or null if UUID not found
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function getIdByUuid(EntityId $entityUuid): int|null
    {
        return PersistenceArticle::where('uuid', $entityUuid->value())->value('id');
    }

    /**
     * Find articles matching complex criteria with filters, search, sorting, and pagination.
     *
     * Returns a domain collection (Articles) that wraps the paginated results
     * with type-safe domain models instead of Eloquent models.
     *
     * @param ArticleCriteriaDTO
     * $criteria Complete filter criteria including:
     * @return Articles
     * Domain collection containing:
     * @throws \Illuminate\Database\QueryException On database failure
     */
    public function findByCriteria(ArticleCriteriaDTO $criteria): Articles
    {
        $query = PersistenceArticle::query()->with(['user', 'kanjis']);

        $this->applyVisibilityFilters($query, $criteria->visibilityRules);
        $this->applyContentFilters($query, $criteria);
        $this->applySorting($query, $criteria->sort);

        $paginatedResults = $query->paginate(
            $criteria->pagination->per_page,
            ['*'],
            'page',
            $criteria->pagination->page
        );

        $domainArticles = $paginatedResults->getCollection()->map(function ($persistenceArticle) {
            return $this->articleMapper->mapToDomain($persistenceArticle);
        });

        $paginatedResults->setCollection($domainArticles);

        return Articles::fromEloquentPaginator($paginatedResults);
    }

    /**
     * Apply permission-based visibility filters to query.
     *
     * Implements complex business rules for article access:
     * - Admins: Can see all articles (publicity = 'all')
     * - Authenticated users: Can see public articles + own private articles
     * - Anonymous users: Can see only public articles
     *
     * @param Builder $query The Eloquent query builder
     * @param array $rules Visibility rules array containing:
     *                     - publicity: 'all' | array of PublicityStatus values
     *                     - access_own_private: bool (can user see their own private articles)
     *                     - user_id: int (required if access_own_private is true)
     * @return void Query is modified by reference
     * @todo Rules should be defined as constants, enums, or ValueObjects instead of raw array
     */
    private function applyVisibilityFilters(Builder $query, array $rules): void
    {
        if (empty($rules)) {
            return;
        }

        if ($rules['publicity'] === 'all') {
            return;
        }

        $query->where(function ($q) use ($rules) {
            if (isset($rules['access_own_private']) && $rules['access_own_private']) {
                // Authenticated user: public articles OR own private articles
                $q->where('publicity', PublicityStatus::PUBLIC)
                    ->orWhere(function ($subQ) use ($rules) {
                        $subQ->where('publicity', PublicityStatus::PRIVATE)
                            ->where('user_id', $rules['user_id']);
                    });
            } else {
                // Anonymous user: only specified publicity statuses
                $q->whereIn('publicity', $rules['publicity']);
            }
        });
    }

    /**
     * Apply content-based filters to query.
     *
     * Handles:
     * - Category filtering (exact match)
     * - Text search (fuzzy match on title_jp and title_en)
     *
     * @param Builder $query The Eloquent query builder
     * @param ArticleCriteriaDTO $criteria The criteria DTO containing:
     *                                     - categoryId: Optional category ID for exact filtering
     *                                     - search: Optional SearchTerm ValueObject for title search
     * @return void Query is modified by reference
     */
    private function applyContentFilters(Builder $query, ArticleCriteriaDTO $criteria): void
    {
        if ($criteria->categoryId !== null) {
            $query->where('category_id', $criteria->categoryId);
        }

        if ($criteria->search !== null) {
            $searchValue = $criteria->search->value;
            $query->where(function ($q) use ($searchValue) {
                $q->where('title_jp', 'LIKE', '%' . $searchValue . '%')
                    ->orWhere('title_en', 'LIKE', '%' . $searchValue . '%');
            });
        }
    }

    /**
     * Apply sorting to query using type-safe criteria.
     *
     * @param Builder $query The Eloquent query builder
     * @param ArticleSortCriteria $sort Sort criteria containing:
     *                                   - field: Enum of allowed sort fields (created_at, title_jp, etc.)
     *                                   - direction: Enum of sort directions (asc, desc)
     * @return void Query is modified by reference
     */
    private function applySorting(Builder $query, ArticleSortCriteria $sort): void
    {
        $query->orderBy($sort->field->value, $sort->direction->value);
    }

    /**
     * Syncs a list of Kanji IDs to an article.
     *
     * @param int $articleId The internal ID of the article.
     * @param int[] $kanjiIds An array of Kanji internal IDs to attach.
     * @return void
     */
    public function syncKanjis(int $articleId, array $kanjiIds): void
    {
        $persistenceArticle = PersistenceArticle::findOrFail($articleId);
        // TODO: use custom query assign kanjis in a single query, instead of individual for each kanji
        $persistenceArticle->kanjis()->sync($kanjiIds);
    }
}
