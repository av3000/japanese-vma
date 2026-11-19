<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Translations\Interfaces\Repositories\TranslationOverrideRepositoryInterface;
use App\Infrastructure\Persistence\Models\Translation;

class TranslationOverrideRepository implements TranslationOverrideRepositoryInterface
{
    public function overridesFor(int $localeId): array
    {
        return Translation::query()
            ->where('translation_locale_id', $localeId)
            ->pluck('value', 'key')
            ->all();
    }

    public function upsert(int $localeId, string $key, string $value, ?int $userId): void
    {
        // A single INSERT ... ON CONFLICT: never insert-then-catch, because a PostgreSQL unique
        // violation aborts the surrounding transaction.
        Translation::query()->upsert(
            [[
                'translation_locale_id' => $localeId,
                'key' => $key,
                'value' => $value,
                'updated_by_user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['translation_locale_id', 'key'],
            ['value', 'updated_by_user_id', 'updated_at'],
        );
    }

    public function delete(int $localeId, string $key): void
    {
        Translation::query()
            ->where('translation_locale_id', $localeId)
            ->where('key', $key)
            ->delete();
    }
}
