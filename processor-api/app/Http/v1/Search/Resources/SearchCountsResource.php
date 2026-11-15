<?php

declare(strict_types=1);

namespace App\Http\v1\Search\Resources;

use App\Domain\Search\DTOs\SearchCountsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SearchCountsDTO $resource
 */
class SearchCountsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Explicit casts keep Scramble from typing the counts as `string|number`. A null count
     * stays null: it means that scope's list rejects the keyword, which is not the same as 0.
     *
     * @return array{q: string, articles: int|null, kanji: int|null, words: int|null, sentences: int|null, radicals: int|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'q' => (string) $this->resource->q,
            'articles' => $this->resource->articles === null ? null : (int) $this->resource->articles,
            'kanji' => $this->resource->kanji === null ? null : (int) $this->resource->kanji,
            'words' => $this->resource->words === null ? null : (int) $this->resource->words,
            'sentences' => $this->resource->sentences === null ? null : (int) $this->resource->sentences,
            'radicals' => $this->resource->radicals === null ? null : (int) $this->resource->radicals,
        ];
    }
}
