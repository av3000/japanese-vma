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
    private const PATHS = [
        'ARTICLE' => 'articles',
        'LIST' => 'catalogues',
        'POST' => 'community',
        'SENTENCE' => 'sentence',
        'KANJI' => 'kanji',
        'WORD' => 'word',
        'RADICAL' => 'radical',
    ];

    public static function forEntity(ObjectTemplateType $type, ?string $uuid): ?string
    {
        $path = self::PATHS[$type->name] ?? null;

        if ($path === null || $uuid === null) {
            return null;
        }

        return rtrim((string) config('app.frontend_url'), '/')."/{$path}/{$uuid}";
    }
}
