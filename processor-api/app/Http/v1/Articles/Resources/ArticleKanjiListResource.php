<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleKanjiListResultDTO;
use App\Domain\JapaneseMaterial\Kanjis\Models\Kanji;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One page of an article's kanji, in the same `{items, pagination}` envelope every other v1
 * list uses, so a client pages it with the hooks it already has.
 *
 * @property ArticleKanjiListResultDTO $resource
 */
class ArticleKanjiListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ArticleKanjiListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, KanjiResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, KanjiResource> $items */
        $items = array_map(
            static fn (Kanji $kanji): KanjiResource => new KanjiResource($kanji),
            $this->resource->items,
        );

        return [
            // Scramble reads the schema off this annotation; without it the generated client
            // types the page as unknown[].
            /** @var array<int, KanjiResource> */
            'items' => $items,
            'pagination' => new PaginationResource($this->resource->pagination->toArray()),
        ];
    }
}
