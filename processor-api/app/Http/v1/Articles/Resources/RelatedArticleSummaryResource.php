<?php

declare(strict_types=1);

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleListItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property ArticleListItemDTO $resource */
class RelatedArticleSummaryResource extends JsonResource
{
    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     title_jp: string,
     *     hashtags: array<int, array{id: int, content: string}>,
     *     views_total: int,
     *     likes_total: int,
     *     comments_total: int
     * }
     */
    public function toArray(Request $request): array
    {
        $item = $this->resource;

        // Explicit casts: Scramble cannot follow `$item->article`, and without them the
        // component documents every scalar as `string` and `hashtags` as untyped.
        return [
            'id' => (int) $item->article->getIdValue(),
            'uuid' => (string) $item->article->getUid()->value(),
            'title_jp' => (string) $item->article->getTitleJp()->value,
            /** @var array<int, array{id: int, content: string}> */
            'hashtags' => array_map(
                static fn (array|object $hashtag): array => [
                    'id' => (int) data_get($hashtag, 'id'),
                    'content' => (string) data_get($hashtag, 'content'),
                ],
                $item->hashtags,
            ),
            'views_total' => (int) ($item->stats?->getViewsCount() ?? 0),
            'likes_total' => (int) ($item->stats?->getLikesCount() ?? 0),
            'comments_total' => (int) ($item->stats?->getCommentsCount() ?? 0),
        ];
    }
}
