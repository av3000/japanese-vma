<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers;

use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\Enums\ArticleFacetDimension;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Builds the Article eligibility predicate: mandatory visibility scope AND user intent.
 *
 * Both the list reader and the facet counters go through here. That is the whole
 * point: if items and counts built their predicates separately they would drift, and
 * a drifting count is how private Articles leak - a row the user may not see still
 * shows up in "N2 (13)".
 *
 * Passing an $exclude dimension gives the disjunctive behaviour facets need: every
 * filter applies except the one whose own options are being counted.
 */
final readonly class ArticleQueryScope
{
    /**
     * PostgreSQL LIKE is case-sensitive, unlike the MySQL collation used before
     * 041cfbf. ILIKE restores case-insensitive title matching.
     */
    private const SEARCH_OPERATOR = 'ILIKE';

    /**
     * PostgreSQL treats a backslash as the LIKE escape by default, so escaping
     * wildcards with it needs no explicit ESCAPE clause.
     */
    private const LIKE_ESCAPE = '\\';

    public function newQuery(
        ArticleListQuery $query,
        ArticleVisibilityScope $scope,
        ?ArticleFacetDimension $exclude = null,
    ): Builder {
        $builder = PersistenceArticle::query();

        $this->applyVisibilityScope($builder, $scope);
        $this->applyFilters($builder, $query, $exclude);

        return $builder;
    }

    /**
     * The mandatory eligibility predicate. Every branch comes from the actor, never
     * from query input.
     */
    public function applyVisibilityScope(Builder $builder, ArticleVisibilityScope $scope): void
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
    public function applyFilters(
        Builder $builder,
        ArticleListQuery $query,
        ?ArticleFacetDimension $exclude = null,
    ): void {
        if ($query->jlptLevels !== [] && $exclude !== ArticleFacetDimension::JLPT_LEVELS) {
            $builder->where(function (Builder $levels) use ($query): void {
                foreach ($query->jlptLevels as $level) {
                    // column() returns an enum-backed name, never raw request input.
                    $levels->orWhere($level->column(), '>', 0);
                }
            });
        }

        if ($query->hashtagIds !== [] && $exclude !== ArticleFacetDimension::HASHTAG_IDS) {
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
     * link table. Link rows must be scoped to the Article entity type and to rows
     * that are not soft-deleted, otherwise an Article keeps matching a tag that was
     * removed from it.
     *
     * @param array<int, int> $hashtagIds
     */
    public function applyHashtagFilter(Builder $builder, array $hashtagIds): void
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
}
