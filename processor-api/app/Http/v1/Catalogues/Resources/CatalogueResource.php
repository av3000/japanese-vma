<?php

namespace App\Http\v1\Catalogues\Resources;

use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\Models\CatalogueStats;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\JlptLevels;
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
        private ?int $itemsCount = null,
        private ?JlptLevels $jlptLevels = null,
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
     *     publicity: PublicityStatus,
     *     owner: AuthorResource,
     *     items_count: int,
     *     hashtags: array<int, HashtagResource>,
     *     engagement: EngagementStatsResource|null,
     *     jlpt_levels: array{n1: int, n2: int, n3: int, n4: int, n5: int, uncommon: int}|null,
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
            'publicity' => $catalogue->getPublicity(),
            'owner' => new AuthorResource([
                'id' => $catalogue->getOwnerId()->value(),
                'uuid' => $catalogue->getOwnerUuid()->value(),
                'name' => $catalogue->getOwnerName()->value(),
            ]),
            'items_count' => (int) ($this->itemsCount ?? 0),
            'hashtags' => HashtagResource::collection($this->hashtags),
            'engagement' => $this->stats ? new EngagementStatsResource($this->stats) : null,
            'jlpt_levels' => self::jlptLevels($this->jlptLevels),
            'created_at' => $catalogue->getCreatedAt()->format('c'),
            'updated_at' => $catalogue->getUpdatedAt()->format('c'),
        ];
    }

    /**
     * Same shape as an article's `jlpt_levels`, or null when it was not requested or the
     * catalogue's type has no JLPT data. Shared with CatalogueDetailResource.
     *
     * @return array{n1: int, n2: int, n3: int, n4: int, n5: int, uncommon: int}|null
     */
    public static function jlptLevels(?JlptLevels $levels): ?array
    {
        if ($levels === null) {
            return null;
        }

        return [
            'n1' => (int) $levels->n1,
            'n2' => (int) $levels->n2,
            'n3' => (int) $levels->n3,
            'n4' => (int) $levels->n4,
            'n5' => (int) $levels->n5,
            'uncommon' => (int) $levels->uncommon,
        ];
    }
}
