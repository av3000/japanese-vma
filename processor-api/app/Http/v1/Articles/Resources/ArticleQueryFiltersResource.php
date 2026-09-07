<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The active filter selection, echoed back in canonical form.
 *
 * Every key is always present, empty when unused, for the same reason `facets` is
 * always an array: a client should not have to branch on whether a key exists. It
 * also keeps the generated type precise, which an open-ended map cannot be.
 */
class ArticleQueryFiltersResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     jlpt_levels: array<int, string>,
     *     hashtag_ids: array<int, int>,
     *     kanji_ids: array<int, int>,
     *     word_ids: array<int, int>,
     *     author_uid: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'jlpt_levels' => array_values(array_map('strval', $this->resource['jlpt_levels'] ?? [])),
            'hashtag_ids' => array_values(array_map('intval', $this->resource['hashtag_ids'] ?? [])),
            'kanji_ids' => array_values(array_map('intval', $this->resource['kanji_ids'] ?? [])),
            'word_ids' => array_values(array_map('intval', $this->resource['word_ids'] ?? [])),
            'author_uid' => $this->resource['author_uid'] ?? null,
        ];
    }
}
