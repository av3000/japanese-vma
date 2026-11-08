<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Translations\Models\TranslationLocale as DomainTranslationLocale;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Database\Seeder;

/**
 * Production-safe and idempotent: makes sure the protected English locale exists and is active.
 * The migration already creates the row; this repairs it if it was ever removed by hand.
 */
class TranslationLocaleSeeder extends Seeder
{
    public function run(): void
    {
        TranslationLocale::query()->updateOrCreate(
            ['code' => DomainTranslationLocale::FALLBACK_CODE],
            ['name' => 'English', 'is_active' => true],
        );
    }
}
