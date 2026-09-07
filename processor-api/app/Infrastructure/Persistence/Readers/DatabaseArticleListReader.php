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
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Repositories\ArticleMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The Eloquent implementation of the Article read port.
 *
 * Everything framework-shaped about Article discovery lives here: the query
 * builder, the publicity predicate, ordering and pagination. Callers above this
 * class see only application and domain types.
 */
final readonly class DatabaseArticleListReader implements ArticleListReaderInterface
{
    /**
     * PostgreSQL LIKE is case-sensitive, unlike the MySQL collation this codebase
     * used before 041cfbf. ILIKE restores the case-insensitive title matching users
     * had, rather than silently keeping the regression.
     */
    private const SEARCH_OPERATOR = 'ILIKE';

    /**
     * PostgreSQL treats a backslash as the LIKE escape character by default, so
     * escaping wildcards with it needs no explicit ESCAPE clause. A user searching
     * for "100%" or "a_b" gets literal matches instead of accidental wildcards.
     */
    private const LIKE_ESCAPE = '\\';

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

    /**
     * OR within one dimension, AND across dimensions. Each dimension is wrapped in
     * its own closure so an OR group can never leak out and widen the others - or,
     * worse, widen the visibility predicate.
     */
    private function applyFilters(Builder $builder, ArticleListQuery $query): void
    {
        if ($query->jlptLevels !== []) {
            $builder->where(function (Builder $levels) use ($query): void {
                foreach ($query->jlptLevels as $level) {
                    // column() returns an enum-backed name, never raw request input.
                    $levels->orWhere($level->column(), '>', 0);
                }
            });
        }

        if ($query->hashtagIds !== []) {
            $this->applyHashtagFilter($builder, $query->hashtagIds);
        }

        if ($query->authorUid !== null) {
            $builder->whereHas('user', function (Builder $user) use ($query): void {
                $user->where('uuid', $query->authorUid);
            });
        }

        if ($query->hasSearch()) {
            $this->applySearch($builder, $query->search->value);
        }

        if ($query->kanjiIds !== []) {
            $builder->whereHas('kanjis', function (Builder $kanjis) use ($query): void {
                $kanjis->whereIn('japanese_kanji_bank_long.id', $query->kanjiIds);
            });
        }

        if ($query->wordIds !== []) {
            $builder->whereHas('words', function (Builder $words) use ($query): void {
                $words->whereIn('japanese_word_bank_long.id', $query->wordIds);
            });
        }

        if ($query->createdBetween !== null) {
            if ($query->createdBetween->from !== null) {
                $builder->where('articles.created_at', '>=', $query->createdBetween->from);
            }

            if ($query->createdBetween->to !== null) {
                $builder->where('articles.created_at', '<=', $query->createdBetween->to);
            }
        }
    }

    /**
     * Hashtag ids are uniquehashtags.id values, reached through the hashtag_entity
     * link table. The link rows must be scoped to the Article entity type and to
     * rows that are not soft-deleted, otherwise an Article keeps matching a tag that
     * was removed from it.
     *
     * @param array<int, int> $hashtagIds
     */
    private function applyHashtagFilter(Builder $builder, array $hashtagIds): void
    {
        $builder->whereExists(function ($link) use ($hashtagIds): void {
            $link->select(DB::raw(1))
                ->from('hashtag_entity')
                ->whereColumn('hashtag_entity.entity_id', 'articles.id')
                ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())
                ->whereNull('hashtag_entity.deleted_at')
                ->whereIn('hashtag_entity.hashtag_id', $hashtagIds);
        });
    }

    /**
     * Title-only search across both languages. Content search, tokenization and
     * relevance ranking are deliberately out of scope for this program.
     */
    private function applySearch(Builder $builder, string $term): void
    {
        $pattern = '%'.$this->escapeLikeWildcards($term).'%';

        $builder->where(function (Builder $matches) use ($pattern): void {
            $matches->where('title_jp', self::SEARCH_OPERATOR, $pattern)
                ->orWhere('title_en', self::SEARCH_OPERATOR, $pattern);
        });
    }

    private function escapeLikeWildcards(string $term): string
    {
        return str_replace(
            [self::LIKE_ESCAPE, '%', '_'],
            [self::LIKE_ESCAPE.self::LIKE_ESCAPE, self::LIKE_ESCAPE.'%', self::LIKE_ESCAPE.'_'],
            $term,
        );
    }

    /**
     * Projection may only change what is loaded, never which rows are eligible.
     */
    private function applyEagerLoads(Builder $builder, ArticleListProjection $projection): void
    {
        if ($projection->includeKanjis) {
            $builder->with('kanjis');
        }

        if ($projection->includeWords) {
            $builder->with('words');
        }
    }

    /**
     * The id tie-breaker is what makes paging stable: without it, Articles sharing a
     * created_at can reorder between requests and a user paging through the list
     * sees duplicates and gaps.
     */
    private function applySorting(Builder $builder, ArticleListQuery $query): void
    {
        $direction = $query->sort->direction->value;

        $builder->orderBy('articles.'.$query->sort->field->value, $direction)
            ->orderBy('articles.id', $direction);
    }
}
