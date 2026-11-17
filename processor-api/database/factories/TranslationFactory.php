<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\Translation;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * A stored override. Each `create()` gets a fresh unique key (`factory.key_N`) so two rows of the
 * same locale never collide; pass `key` to target a real catalogue key. The locale is a new
 * non-English locale unless `for($locale, 'locale')` is used.
 */
class TranslationFactory extends Factory
{
    protected $model = Translation::class;

    private static int $sequence = 0;

    public function definition(): array
    {
        $n = ++self::$sequence;

        return [
            'translation_locale_id' => TranslationLocale::factory(),
            'key' => "factory.key_{$n}",
            'value' => "Oversat tekst {$n}",
            'updated_by_user_id' => null,
        ];
    }
}
