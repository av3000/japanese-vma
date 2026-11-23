<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Public React client URLs for entities, built from `app.frontend_url` (FRONTEND_URL) so no host
 * is hard-coded. Every detail route takes the entity uuid.
 */
final class ClientUrls
{
    public static function forEntity(ObjectTemplateType $type, ?string $uuid): ?string
    {
        $path = self::path($type);

        if ($path === null || $uuid === null) {
            return null;
        }

        return rtrim((string) config('app.frontend_url'), '/')."/{$path}/{$uuid}";
    }

    /**
     * The client route segment per type. Artists, lyrics and comments have no detail page.
     */
    private static function path(ObjectTemplateType $type): ?string
    {
        return match ($type) {
            ObjectTemplateType::ARTICLE => 'articles',
            ObjectTemplateType::LIST => 'catalogues',
            ObjectTemplateType::POST => 'community',
            ObjectTemplateType::SENTENCE => 'sentence',
            ObjectTemplateType::KANJI => 'kanji',
            ObjectTemplateType::WORD => 'word',
            ObjectTemplateType::RADICAL => 'radical',
            ObjectTemplateType::ARTIST, ObjectTemplateType::LYRIC, ObjectTemplateType::COMMENT => null,
        };
    }
}
