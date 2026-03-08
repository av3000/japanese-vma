<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\CatalogueStats;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

/**
 * @property Catalogue $resource
 */
class CatalogueResource extends JsonResource
{
    public function __construct(
        Catalogue $catalogue,
        private ?CatalogueStats $stats = null,
        private array $hashtags = [],
        private ?int $itemsCount = null
    ) {
        parent::__construct($catalogue);
    }

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
            'owner' => [
                'id' => $catalogue->getOwnerId()->value(),
                'uuid' => $catalogue->getOwnerUuid()->value(),
                'name' => $catalogue->getOwnerName()->value(),
            ],
            'items_count' => $this->itemsCount ?? 0,
            'hashtags' => $this->hashtags,
            'engagement' => $this->stats ? [
                'likes_count' => $this->stats->getLikesCount(),
                'views_count' => $this->stats->getViewsCount(),
                'downloads_count' => $this->stats->getDownloadsCount(),
                'comments_count' => $this->stats->getCommentsCount(),
            ] : null,
            'created_at' => $catalogue->getCreatedAt()->format('c'),
            'updated_at' => $catalogue->getUpdatedAt()->format('c'),
        ];
    }
}
