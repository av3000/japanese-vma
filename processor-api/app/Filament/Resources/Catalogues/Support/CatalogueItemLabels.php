<?php

declare(strict_types=1);

namespace App\Filament\Resources\Catalogues\Support;

use App\Application\Engagement\Interfaces\Readers\EntityReferenceReaderInterface;
use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\CatalogueItem;
use Illuminate\Contracts\Pagination\Paginator;
use WeakMap;

/**
 * Labels for a page of catalogue items: one reader call per page, one query per item type
 * present (a catalogue holds a single type, so normally one).
 */
final class CatalogueItemLabels
{
    /** @var WeakMap<object, array<string, EntityReferenceDTO>>|null */
    private static ?WeakMap $pages = null;

    /**
     * What a catalogue of this type holds. Known lists hold the same things as their custom twin.
     */
    public static function itemType(SavedListType $catalogueType): ?ObjectTemplateType
    {
        return $catalogueType->itemType();
    }

    /**
     * @param iterable<CatalogueItem>|Paginator $page
     */
    public static function of(CatalogueItem $item, ?ObjectTemplateType $type, object $page): ?EntityReferenceDTO
    {
        if ($type === null) {
            return null;
        }

        self::$pages ??= new WeakMap();
        self::$pages[$page] ??= self::resolve($type, $page instanceof Paginator ? $page->items() : $page);

        return self::$pages[$page][EntityReferenceDTO::key($type, $item->real_object_id)]
            ?? EntityReferenceDTO::missing($type, $item->real_object_id);
    }

    /**
     * @param iterable<CatalogueItem> $items
     *
     * @return array<string, EntityReferenceDTO>
     */
    private static function resolve(ObjectTemplateType $type, iterable $items): array
    {
        $references = [];

        foreach ($items as $item) {
            $references[] = [$type, (int) $item->real_object_id];
        }

        return $references === [] ? [] : app(EntityReferenceReaderInterface::class)->find($references);
    }
}
