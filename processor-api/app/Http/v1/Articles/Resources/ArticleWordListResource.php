<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleWordListResultDTO;
use App\Domain\JapaneseMaterial\Words\Models\Word;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One page of an article's words. Replaces the `{success, words, message}` envelope the
 * numeric-id route returned, which carried no page metadata at all (issue #268).
 *
 * @property ArticleWordListResultDTO $resource
 */
class ArticleWordListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ArticleWordListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, ArticleWordResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<int, ArticleWordResource> $items */
        $items = array_map(
            static fn (Word $word): ArticleWordResource => new ArticleWordResource($word),
            $this->resource->items,
        );

        return [
            // Scramble reads the schema off this annotation; without it the generated client
            // types the page as unknown[].
            /** @var array<int, ArticleWordResource> */
            'items' => $items,
            'pagination' => new PaginationResource($this->resource->pagination->toArray()),
        ];
    }
}
