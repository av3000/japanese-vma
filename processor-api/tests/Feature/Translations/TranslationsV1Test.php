<?php

declare(strict_types=1);

namespace Tests\Feature\Translations;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TranslationsV1Test extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------ locales (AC-1)

    public function test_a_fresh_database_lists_english_as_the_fallback(): void
    {
        $this->getJson('/api/v1/translations/locales')
            ->assertOk()
            ->assertExactJson([
                'items' => [
                    ['code' => 'en', 'name' => 'English', 'is_fallback' => true],
                ],
            ]);
    }

    public function test_active_locales_are_listed_english_first_and_only_english_is_the_fallback(): void
    {
        TranslationLocale::factory()->code('sv', 'Svenska')->create();
        TranslationLocale::factory()->create();

        $this->getJson('/api/v1/translations/locales')
            ->assertOk()
            ->assertExactJson([
                'items' => [
                    ['code' => 'en', 'name' => 'English', 'is_fallback' => true],
                    ['code' => 'da', 'name' => 'Dansk', 'is_fallback' => false],
                    ['code' => 'sv', 'name' => 'Svenska', 'is_fallback' => false],
                ],
            ]);
    }

    public function test_an_inactive_locale_is_not_listed(): void
    {
        TranslationLocale::factory()->inactive()->create();

        $this->getJson('/api/v1/translations/locales')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.code', 'en');
    }

    public function test_locales_route_is_not_swallowed_by_the_locale_parameter(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/api/v1/translations/locales', 'GET'),
        );

        $this->assertSame('locales', $route->getActionMethod());
        $this->assertSame('show', app('router')->getRoutes()->match(
            Request::create('/api/v1/translations/en', 'GET'),
        )->getActionMethod());
    }

    public function test_locale_reads_are_public(): void
    {
        $this->getJson('/api/v1/translations/locales')->assertOk();
        $this->getJson('/api/v1/translations/en')->assertOk();
    }

    // ------------------------------------------------------------ en bundle (AC-2)

    public function test_the_english_bundle_has_the_documented_shape(): void
    {
        $response = $this->getJson('/api/v1/translations/en');

        $response->assertOk()
            ->assertJsonMissingPath('data')
            ->assertJsonMissingPath('success')
            ->assertJsonStructure(['locale', 'fallback_locale', 'messages'])
            ->assertJsonPath('locale', 'en')
            ->assertJsonPath('fallback_locale', 'en');
    }

    public function test_the_english_bundle_is_the_flattened_catalogue_with_no_empty_value(): void
    {
        $catalogue = app(EnglishSourceCatalogueInterface::class)->messages();

        $messages = $this->getJson('/api/v1/translations/en')->assertOk()->json('messages');

        $this->assertSame($catalogue, $messages);
        $this->assertNotEmpty($messages);

        foreach ($messages as $key => $value) {
            $this->assertIsString($value, "Key {$key} is not text.");
            $this->assertNotSame('', trim($value), "Key {$key} is empty.");
            $this->assertStringContainsString('.', $key, "Key {$key} is not a dotted key.");
        }
    }

    public function test_the_english_bundle_carries_the_starter_labels_word_for_word(): void
    {
        $messages = $this->getJson('/api/v1/translations/en')->assertOk()->json('messages');

        $this->assertSame('Log In', $messages['account.log_in']);
        $this->assertSame('Sign Up', $messages['account.sign_up']);
        $this->assertSame('Email', $messages['auth.login.email']);
        $this->assertSame('Cancel', $messages['common.cancel']);
    }

    public function test_an_active_non_english_locale_answers_with_its_own_code_and_the_english_messages(): void
    {
        TranslationLocale::factory()->create();

        $english = $this->getJson('/api/v1/translations/en')->json('messages');

        $this->getJson('/api/v1/translations/da')
            ->assertOk()
            ->assertJsonPath('locale', 'da')
            ->assertJsonPath('fallback_locale', 'en')
            ->assertJsonPath('messages', $english);
    }

    // ------------------------------------------------------------ unknown locale (AC-3)

    public function test_an_unknown_locale_is_a_typed_404(): void
    {
        $this->getJson('/api/v1/translations/xx')
            ->assertNotFound()
            ->assertJson([
                'type' => 'https://tools.ietf.org/html/rfc7231#section-6.5.4',
                'title' => 'Locale not found',
                'status' => 404,
                'instance' => 'api/v1/translations/xx',
            ])
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'timestamp', 'errorMessage']);
    }

    public function test_an_inactive_locale_is_indistinguishable_from_an_unknown_one(): void
    {
        TranslationLocale::factory()->inactive()->create();

        $this->getJson('/api/v1/translations/da')
            ->assertNotFound()
            ->assertJsonPath('title', 'Locale not found');
    }

    public function test_the_locale_code_is_matched_exactly(): void
    {
        $this->getJson('/api/v1/translations/EN')->assertNotFound();
    }

    public function test_a_region_style_code_that_is_not_a_locale_is_the_typed_404(): void
    {
        $this->getJson('/api/v1/translations/en-US')
            ->assertNotFound()
            ->assertJsonPath('title', 'Locale not found');
    }

    /**
     * The router decodes the segment before it reaches the query. `%FF` is not valid UTF-8 and
     * PostgreSQL rejects it, so a segment that cannot be a locale code must be turned away
     * before any query runs: a 404, never a 500.
     */
    #[DataProvider('junkSegments')]
    public function test_a_segment_that_cannot_be_a_locale_code_is_a_404_and_never_a_500(string $segment): void
    {
        $this->getJson('/api/v1/translations/'.$segment)->assertNotFound();
    }

    /** @return array<string, array{string}> */
    public static function junkSegments(): array
    {
        return [
            'invalid utf-8 byte' => ['%FF'],
            'nul byte' => ['%00'],
            'space' => ['en%20x'],
            'too long' => ['abcdefghijklmnop'],
            'digits' => ['12'],
        ];
    }
}
