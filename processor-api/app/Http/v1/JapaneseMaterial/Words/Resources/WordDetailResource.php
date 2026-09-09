<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Resources;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Domain\JapaneseMaterial\Words\DTOs\WordDetailResultDTO;
use App\Http\v1\Articles\Resources\RelatedArticleSummaryResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property WordDetailResultDTO $resource */
class WordDetailResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $payload = (new WordResource($this->resource->word))->resolve($request);

        if ($this->resource->kanjis !== null) {
            $payload['kanjis'] = array_map(
                static fn (Kanji $kanji): array => (new KanjiResource($kanji))->resolve($request),
                $this->resource->kanjis,
            );
        }

        if ($this->resource->articles !== null) {
            $payload['articles'] = array_map(
                static fn (ArticleListItemDTO $article): array => (new RelatedArticleSummaryResource($article))->resolve($request),
                $this->resource->articles,
            );
        }

        return $payload;
    }
}
