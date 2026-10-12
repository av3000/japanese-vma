<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Shared\DTOs\EntityReferenceDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Catalogues\CatalogueResource;
use App\Filament\Resources\Posts\PostResource;

/**
 * How the panel shows a pointer to another entity: a short label, "Deleted <type>" when it is
 * gone, and a link to this panel's own page when there is one the viewer may open, otherwise to
 * the public client page.
 */
final class EntityReferenceLinks
{
    public static function label(?EntityReferenceDTO $reference): string
    {
        if ($reference === null) {
            return 'Unknown entity';
        }

        return $reference->exists()
            ? mb_strimwidth((string) $reference->label, 0, 60, '…')
            : 'Deleted '.mb_strtolower($reference->type->label());
    }

    public static function url(?EntityReferenceDTO $reference): ?string
    {
        if ($reference === null || ! $reference->exists()) {
            return null;
        }

        $resource = match ($reference->type) {
            ObjectTemplateType::ARTICLE => ArticleResource::class,
            ObjectTemplateType::POST => PostResource::class,
            ObjectTemplateType::LIST => CatalogueResource::class,
            default => null,
        };

        if ($resource !== null && $resource::canViewAny()) {
            return $resource::getUrl('view', ['record' => $reference->uuid]);
        }

        return ClientUrls::forEntity($reference->type, $reference->uuid);
    }
}
