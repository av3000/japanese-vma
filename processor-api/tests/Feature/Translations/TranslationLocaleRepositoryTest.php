<?php

declare(strict_types=1);

namespace Tests\Feature\Translations;

use App\Application\Translations\Interfaces\Repositories\TranslationLocaleRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationLocaleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_returns_the_new_locale(): void
    {
        $locale = app(TranslationLocaleRepositoryInterface::class)->create('da', 'Dansk', true);

        self::assertSame('da', $locale?->getCode());
        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'is_active' => true]);
    }

    public function test_create_returns_null_when_the_code_is_taken_instead_of_throwing(): void
    {
        $repository = app(TranslationLocaleRepositoryInterface::class);

        self::assertNull($repository->create('en', 'English again', true));
        $this->assertDatabaseCount('translation_locales', 1);
    }
}
