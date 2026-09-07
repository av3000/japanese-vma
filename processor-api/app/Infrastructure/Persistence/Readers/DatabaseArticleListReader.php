<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\DTOs\ArticleListReadResult;
use App\Application\Articles\DTOs\ArticlePaginationDTO;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent implementation of the Article read port.
 *
 * Everything framework-shaped about Article discovery lives here: the query
 * builder, the publicity predicate, ordering and pagination. Callers above this
 * class see only application and domain types.
 *
 * AFM-03 replaces the category and singular relation filters below with the
 * canonical plural contract, and adds the deterministic id tie-breaker.
 */
final readonly class DatabaseArticleListReader implements ArticleListReaderInterface
{
    public function __construct(
        private ArticleMapper $articleMapper,
    ) {
    }

    public function search(
        ArticleListQuery $query,
        ArticleVisibilityScope $scope,
        ArticleListProjection $projection,
    ): ArticleListReadResult {
        $builder = PersistenceArticle::query()->with(['user']);

        $this->applyVisibilityScope($builder, $scope);
        $this->applyFilters($builder, $query);
        $this->applyEagerLoads($builder, $projection);
        $this->applySorting($builder, $query);

        $paginator = $builder->paginate(
            $query->pagination->per_page,
            ['*'],
            'page',
            $query->pagination->page,
        );

        $includeOptions = new ArticleIncludeOptionsDTO(
            include_kanjis: $projection->includeKanjis,
            include_words: $projection->includeWords,
        );

        $articles = $paginator
            ->getCollection()
            ->map(fn (PersistenceArticle $article): DomainArticle => $this->articleMapper->mapToDomain($article, $includeOptions))
            ->values()
            ->all();

        return new ArticleListReadResult(
            articles: $articles,
            pagination: new ArticlePaginationDTO(
                page: $paginator->currentPage(),
                perPage: $paginator->perPage(),
                total: $paginator->total(),
                lastPage: $paginator->lastPage(),
                hasMore: $paginator->hasMorePages(),
            ),
        );
    }

    /**
     * The mandatory eligibility predicate. Every branch here comes from the actor,
     * never from query input.
     */
    private function applyVisibilityScope(Builder $builder, ArticleVisibilityScope $scope): void
    {
        if ($scope->isUnrestricted()) {
            return;
        }

        $ownerId = $scope->privateOwnerId();

        $builder->where(function (Builder $scoped) use ($ownerId): void {
            $scoped->where('publicity', PublicityStatus::PUBLIC);

            if ($ownerId !== null) {
                $scoped->orWhere(function (Builder $owned) use ($ownerId): void {
                    $owned->where('publicity', PublicityStatus::PRIVATE)
                        ->where('user_id', $ownerId);
                });
            }
        });
    }

    private function applyFilters(Builder $builder, ArticleListQuery $query): void
    {
        if ($query->categoryId !== null) {
            // Preserved from the pre-AFM-02 repository. `articles.category_id` does not
            // exist, so this branch fails at the database. AFM-03 replaces it with
            // jlpt_levels; it is carried over unchanged so this issue stays behaviour-preserving.
            $builder->where('category_id', $query->categoryId);
        }

        if ($query->authorUid !== null) {
            $builder->whereHas('user', function (Builder $user) use ($query): void {
                $user->where('uuid', $query->authorUid);
            });
        }

        if ($query->search !== null) {
            $term = $query->search->value;

            $builder->where(function (Builder $matches) use ($term): void {
                $matches->where('title_jp', 'LIKE', '%'.$term.'%')
                    ->orWhere('title_en', 'LIKE', '%'.$term.'%');
            });
        }

        if ($query->kanjiId !== null) {
            $builder->whereHas('kanjis', function (Builder $kanjis) use ($query): void {
                $kanjis->whereKey($query->kanjiId);
            });
        }

        if ($query->wordId !== null) {
            $builder->whereHas('words', function (Builder $words) use ($query): void {
                $words->whereKey($query->wordId);
            });
        }
    }

    /**
     * Projection may only change what is loaded, never which rows are eligible.
     */
    private function applyEagerLoads(Builder $builder, ArticleListProjection $projection): void
    {
        if ($projection->includeKanjis) {
            $builder->with('kanjis');
        }
    }

    private function applySorting(Builder $builder, ArticleListQuery $query): void
    {
        $builder->orderBy($query->sort->field->value, $query->sort->direction->value);
    }
}
