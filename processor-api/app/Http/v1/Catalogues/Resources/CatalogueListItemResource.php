<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CatalogueListItemDTO;
use App\Http\v1\Engagement\Resources\EngagementStatsResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CatalogueListItemDTO $resource
 */
class CatalogueListItemResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(CatalogueListItemDTO $item)
    {
        parent::__construct($item);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     type: int,
     *     type_label: string,
     *     title: string,
     *     description: string|null,
     *     publicity: int,
     *     owner: AuthorResource,
     *     items_count: int,
     *     hashtags: array<int, HashtagResource>,
     *     engagement: EngagementStatsResource|null,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var CatalogueListItemDTO $item */
        $item = $this->resource;
        $catalogue = $item->catalogue;

        return [
            'id' => $catalogue->getIdValue(),
            'uuid' => (string) $catalogue->getUid(),
            'type' => $catalogue->getType()->value,
            'type_label' => $catalogue->getTypeLabel(),
            'title' => (string) $catalogue->getTitle(),
            'description' => $catalogue->getDescription()->isEmpty() ? null : (string) $catalogue->getDescription(),
            'publicity' => $catalogue->getPublicity()->value,
            'owner' => new AuthorResource([
                'id' => $catalogue->getOwnerId()->value(),
                'uuid' => $catalogue->getOwnerUuid()->value(),
                'name' => $catalogue->getOwnerName()->value(),
            ]),
            'items_count' => $item->itemsCount,
            'hashtags' => HashtagResource::collection($item->hashtags),
            'engagement' => $item->stats ? new EngagementStatsResource($item->stats) : null,
            'created_at' => $catalogue->getCreatedAt()->format('c'),
            'updated_at' => $catalogue->getUpdatedAt()->format('c'),
        ];
    }
}
