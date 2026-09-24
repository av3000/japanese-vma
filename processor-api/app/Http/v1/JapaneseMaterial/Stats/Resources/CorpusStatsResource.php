<?php

namespace App\Http\v1\JapaneseMaterial\Stats\Resources;

use App\Domain\JapaneseMaterial\Stats\DTOs\CorpusStatsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CorpusStatsDTO $resource
 */
class CorpusStatsResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Explicit int casts keep Scramble from typing the counts as `string|number`.
     *
     * @return array{radicals: int, kanjis: int, words: int, sentences: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'radicals' => (int) $this->resource->radicals,
            'kanjis' => (int) $this->resource->kanjis,
            'words' => (int) $this->resource->words,
            'sentences' => (int) $this->resource->sentences,
        ];
    }
}
