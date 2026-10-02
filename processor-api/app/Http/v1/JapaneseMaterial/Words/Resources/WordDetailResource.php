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

    /**
     * The word's own fields come from `WordResource` through `merge`, and the optional
     * includes through `when`, so the documented schema is derived from the same code that
     * builds the payload instead of a second, hand-written shape.
     */
    public function toArray(Request $request): array
    {
        $kanjis = $this->resource->kanjis;
        $articles = $this->resource->articles;

        return [
            $this->merge((new WordResource($this->resource->word))->toArray($request)),
            /** @var array<int, KanjiResource> */
            'kanjis' => $this->when(
                $kanjis !== null,
                static fn (): array => array_map(
                    static fn (Kanji $kanji): KanjiResource => new KanjiResource($kanji),
                    $kanjis ?? [],
                ),
            ),
            /** @var array<int, RelatedArticleSummaryResource> */
            'articles' => $this->when(
                $articles !== null,
                static fn (): array => array_map(
                    static fn (ArticleListItemDTO $article): RelatedArticleSummaryResource => new RelatedArticleSummaryResource($article),
                    $articles ?? [],
                ),
            ),
        ];
    }
}
