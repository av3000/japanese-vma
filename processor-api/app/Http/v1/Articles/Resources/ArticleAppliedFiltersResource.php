<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Enums\ArticleJlptLevel;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The active filter selection, echoed back in canonical form.
 *
 * Every key is always present, empty or null when unused, for the same reason
 * `facets` is always an array: a client should not have to branch on whether a key
 * exists, and the generated type stays precise.
 *
 * @property ArticleQueryCriteria $resource
 */
class ArticleAppliedFiltersResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     jlpt_levels: array<int, string>,
     *     hashtag_ids: array<int, int>,
     *     kanji_ids: array<int, int>,
     *     word_ids: array<int, int>,
     *     author_uid: string|null,
     *     created_from: string|null,
     *     created_to: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $criteria = $this->resource;

        /** @var array<int, string> $jlptLevels */
        $jlptLevels = array_values(array_map(
            static fn (ArticleJlptLevel $level): string => $level->value,
            $criteria->jlptLevels,
        ));

        /** @var array<int, int> $hashtagIds */
        $hashtagIds = array_values(array_map('intval', $criteria->hashtagIds));

        /** @var array<int, int> $kanjiIds */
        $kanjiIds = array_values(array_map('intval', $criteria->kanjiIds));

        /** @var array<int, int> $wordIds */
        $wordIds = array_values(array_map('intval', $criteria->wordIds));

        return [
            /** @var array<int, string> */
            'jlpt_levels' => $jlptLevels,
            /** @var array<int, int> */
            'hashtag_ids' => $hashtagIds,
            /** @var array<int, int> */
            'kanji_ids' => $kanjiIds,
            /** @var array<int, int> */
            'word_ids' => $wordIds,
            'author_uid' => $criteria->authorUid,
            /** @var string|null */
            'created_from' => $criteria->createdBetween?->from?->format('Y-m-d'),
            /** @var string|null */
            'created_to' => $criteria->createdBetween?->to?->format('Y-m-d'),
        ];
    }
}
