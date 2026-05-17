<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\CatalogueStats;
use App\Http\v1\Engagement\Resources\EngagementStatsResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Catalogue $resource
 */
class CatalogueResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        Catalogue $catalogue,
        private ?CatalogueStats $stats = null,
        private array $hashtags = [],
        private ?int $itemsCount = null
    ) {
        parent::__construct($catalogue);
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
        /** @var Catalogue $catalogue */
        $catalogue = $this->resource;

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
            'items_count' => (int) ($this->itemsCount ?? 0),
            'hashtags' => HashtagResource::collection($this->hashtags),
            'engagement' => $this->stats ? new EngagementStatsResource($this->stats) : null,
            'created_at' => $catalogue->getCreatedAt()->format('c'),
            'updated_at' => $catalogue->getUpdatedAt()->format('c'),
        ];
    }
}
