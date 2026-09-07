<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Readers\ArticleFacets;

use App\Application\Articles\DTOs\ArticleFacetDTO;
use App\Application\Articles\DTOs\ArticleFacetValueDTO;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\Enums\ArticleFacetDimension;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Readers\ArticleQueryScope;
use Illuminate\Support\Facades\DB;

/**
 * Counts eligible Articles per hashtag.
 *
 * Keys are uniquehashtags.id, reached through hashtag_entity, matching the
 * hashtag_ids filter contract exactly. Link rows are restricted to the Article
 * entity type and to rows that are not soft-deleted, so a tag removed from an
 * Article stops being counted for it.
 */
final readonly class HashtagFacetCounter
{
    /**
     * Cap on returned values. An unbounded tag cloud is neither useful nor cheap to
     * render; selected values are added back on top so a user never loses sight of a
     * filter they applied.
     */
    private const MAX_VALUES = 50;

    public function __construct(
        private ArticleQueryScope $queryScope,
    ) {
    }

    public function count(ArticleListQuery $query, ArticleVisibilityScope $scope): ArticleFacetDTO
    {
        // Disjunctive: every filter applies except the hashtag selection itself.
        $eligible = $this->queryScope
            ->newQuery($query, $scope, ArticleFacetDimension::HASHTAG_IDS)
            ->toBase()
            ->select('articles.id');

        $rows = DB::table('hashtag_entity')
            ->join('uniquehashtags', 'uniquehashtags.id', '=', 'hashtag_entity.hashtag_id')
            ->where('hashtag_entity.entity_type_id', ObjectTemplateType::ARTICLE->getLegacyId())
            ->whereNull('hashtag_entity.deleted_at')
            ->whereIn('hashtag_entity.entity_id', $eligible)
            ->groupBy('uniquehashtags.id', 'uniquehashtags.content')
            ->select([
                'uniquehashtags.id as hashtag_id',
                'uniquehashtags.content as label',
                DB::raw('COUNT(DISTINCT hashtag_entity.entity_id) as article_count'),
            ])
            // Count descending, then id ascending so ties are stable across requests
            // rather than reordering on every page load.
            ->orderByDesc('article_count')
            ->orderBy('uniquehashtags.id')
            ->get();

        $selected = array_map('intval', $query->hashtagIds);

        $values = [];
        $seen = [];

        foreach ($rows as $row) {
            $id = (int) $row->hashtag_id;

            if (count($values) >= self::MAX_VALUES && ! in_array($id, $selected, true)) {
                continue;
            }

            $seen[] = $id;
            $values[] = new ArticleFacetValueDTO(
                key: (string) $id,
                label: (string) $row->label,
                count: (int) $row->article_count,
                selected: in_array($id, $selected, true),
            );
        }

        // A selection whose disjunctive count is zero must stay visible, otherwise the
        // control the user just used disappears from under them.
        foreach ($this->labelsFor(array_values(array_diff($selected, $seen))) as $id => $label) {
            $values[] = new ArticleFacetValueDTO(
                key: (string) $id,
                label: $label,
                count: 0,
                selected: true,
            );
        }

        return ArticleFacetDTO::multi(
            ArticleFacetDimension::HASHTAG_IDS->value,
            ArticleFacetDimension::HASHTAG_IDS->label(),
            $values,
        );
    }

    /**
     * @param array<int, int> $hashtagIds
     *
     * @return array<int, string>
     */
    private function labelsFor(array $hashtagIds): array
    {
        if ($hashtagIds === []) {
            return [];
        }

        return DB::table('uniquehashtags')
            ->whereIn('id', $hashtagIds)
            ->orderBy('id')
            ->pluck('content', 'id')
            ->map(static fn ($content): string => (string) $content)
            ->all();
    }
}
