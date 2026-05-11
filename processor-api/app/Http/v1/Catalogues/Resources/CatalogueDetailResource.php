<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\DTOs\CatalogueDetailDTO;
use App\Http\v1\Engagement\Resources\CatalogueDetailEngagementResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property CatalogueDetailDTO $resource
 */
class CatalogueDetailResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(CatalogueDetailDTO $detail)
    {
        parent::__construct($detail);
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
     *     engagement: CatalogueDetailEngagementResource|null,
     *     items: array<int, mixed>,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var CatalogueDetailDTO $detail */
        $detail = $this->resource;
        $catalogue = $detail->catalogue;

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
            'items_count' => $detail->itemsCount,
            'hashtags' => HashtagResource::collection($detail->hashtags),
            'engagement' => new CatalogueDetailEngagementResource($detail->stats, $detail->isLikedByViewer),
            'items' => $detail->items,
            'created_at' => $catalogue->getCreatedAt()->format('c'),
            'updated_at' => $catalogue->getUpdatedAt()->format('c'),
        ];
    }
}
