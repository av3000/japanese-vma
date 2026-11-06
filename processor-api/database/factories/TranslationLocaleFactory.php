<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Non-English locales only: `en` already exists from the migration, and its code is unique.
 * The default is an active locale so a test that asks for "a language" gets one clients can see.
 */
class TranslationLocaleFactory extends Factory
{
    protected $model = TranslationLocale::class;

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
