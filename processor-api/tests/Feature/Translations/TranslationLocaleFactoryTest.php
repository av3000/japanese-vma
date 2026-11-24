<?php

declare(strict_types=1);

namespace Tests\Feature\Translations;

use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationLocaleFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_single_locale_defaults_to_active_danish(): void
    {
        $locale = TranslationLocale::factory()->create();

        $this->assertSame('da', $locale->code);
        $this->assertSame('Dansk', $locale->name);
        $this->assertTrue($locale->is_active);
    }

    public function test_several_locales_in_one_call_get_distinct_non_english_codes(): void
    {
        $codes = TranslationLocale::factory()->count(4)->create()->pluck('code')->all();

        $this->assertSame($codes, array_values(array_unique($codes)));
        $this->assertNotContains('en', $codes);
    }

    public function test_an_explicit_code_overrides_the_default(): void
    {
        $locale = TranslationLocale::factory()->code('sv', 'Svenska')->create();

        $this->assertSame('sv', $locale->code);
        $this->assertSame('Svenska', $locale->name);
    }
}
