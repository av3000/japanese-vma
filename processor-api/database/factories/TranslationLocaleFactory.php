<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Non-English locales only: `en` already exists from the migration, and `code` is unique.
 *
 * The default is an active locale, so a test that asks for "a language" gets one clients can see.
 * One `create()` is always Danish. `count(n)` walks a list of other codes so the rows do not
 * collide. Two separate `create()` calls both start at Danish: give the second one `code()`.
 */
class TranslationLocaleFactory extends Factory
{
    protected $model = TranslationLocale::class;

    public function configure(): static
    {
        return $this->sequence(
            ['code' => 'da', 'name' => 'Dansk'],
            ['code' => 'sv', 'name' => 'Svenska'],
            ['code' => 'nb', 'name' => 'Norsk bokmål'],
            ['code' => 'fi', 'name' => 'Suomi'],
            ['code' => 'de', 'name' => 'Deutsch'],
            ['code' => 'fr', 'name' => 'Français'],
            ['code' => 'es', 'name' => 'Español'],
            ['code' => 'nl', 'name' => 'Nederlands'],
        );
    }

    public function definition(): array
    {
        return [
            'code' => 'da',
            'name' => 'Dansk',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function code(string $code, string $name): static
    {
        return $this->state(['code' => $code, 'name' => $name]);
    }
}
