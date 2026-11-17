<?php

declare(strict_types=1);

namespace Tests\Feature\Translations;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Application\Translations\Services\TranslationEditorServiceInterface;
use App\Infrastructure\Persistence\Models\Translation;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Overrides merged over English in `GET /api/v1/translations/{locale}` (#661: AC-1, AC-2, AC-5, AC-6).
 */
class TranslationOverridesBundleTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function english(): array
    {
        return app(EnglishSourceCatalogueInterface::class)->messages();
    }

    private function override(TranslationLocale $locale, string $key, string $value): void
    {
        Translation::factory()->for($locale, 'locale')->create(['key' => $key, 'value' => $value]);
    }

    // ------------------------------------------------------------ AC-1

    public function test_every_english_key_is_present_overrides_are_translated_and_the_rest_is_english(): void
    {
        $da = TranslationLocale::factory()->create();
        $this->override($da, 'account.log_in', 'Log ind');
        $this->override($da, 'common.save', 'Gem');
        $english = $this->english();

        $response = $this->getJson('/api/v1/translations/da')
            ->assertOk()
            ->assertJsonPath('locale', 'da')
            ->assertJsonPath('fallback_locale', 'en');
        $messages = $response->json('messages');

        $this->assertSame(array_keys($english), array_keys($messages));
        $this->assertSame('Log ind', $messages['account.log_in']);
        $this->assertSame('Gem', $messages['common.save']);
        $this->assertSame($english['account.sign_up'], $messages['account.sign_up']);
        $this->assertSame($english['common.cancel'], $messages['common.cancel']);
    }

    public function test_one_locales_overrides_do_not_leak_into_another_locales_bundle(): void
    {
        $da = TranslationLocale::factory()->create();
        TranslationLocale::factory()->code('sv', 'Svenska')->create();
        $this->override($da, 'account.log_in', 'Log ind');

        // `assertJsonPath` splits on dots, and the keys are dotted: compare the whole map.
        $this->getJson('/api/v1/translations/sv')
            ->assertOk()
            ->assertJsonPath('messages', $this->english());
    }

    public function test_the_english_bundle_ignores_overrides_stored_for_it(): void
    {
        $en = TranslationLocale::query()->where('code', 'en')->firstOrFail();
        $this->override($en, 'account.log_in', 'Sign in');

        $this->getJson('/api/v1/translations/en')
            ->assertOk()
            ->assertJsonPath('messages', $this->english());
    }

    public function test_a_blank_stored_value_never_hides_the_english_label(): void
    {
        $da = TranslationLocale::factory()->create();
        $this->override($da, 'account.log_in', '   ');

        $this->getJson('/api/v1/translations/da')
            ->assertOk()
            ->assertJsonPath('messages', $this->english());
    }

    public function test_a_value_saved_through_the_editor_service_is_in_the_next_response(): void
    {
        TranslationLocale::factory()->create();
        $this->getJson('/api/v1/translations/da')->assertJsonPath('messages', $this->english());

        $saved = app(TranslationEditorServiceInterface::class)->saveOverride('da', 'common.save', ' Gem ', null);

        $this->assertTrue($saved->isSuccess());
        $this->getJson('/api/v1/translations/da')->assertOk()->assertJsonPath('messages', array_merge(
            $this->english(),
            ['common.save' => 'Gem'],
        ));
    }

    // ------------------------------------------------------------ AC-2

    public function test_an_inactive_locale_is_the_typed_404_and_its_overrides_stay_stored(): void
    {
        $da = TranslationLocale::factory()->inactive()->create();
        $this->override($da, 'account.log_in', 'Log ind');

        $this->getJson('/api/v1/translations/da')
            ->assertNotFound()
            ->assertJsonPath('title', 'Locale not found')
            ->assertJsonPath('status', 404);

        $this->assertSame(1, Translation::query()->where('translation_locale_id', $da->id)->count());
    }

    public function test_reactivating_a_locale_brings_its_overrides_back(): void
    {
        $da = TranslationLocale::factory()->inactive()->create();
        $this->override($da, 'account.log_in', 'Log ind');
        $this->getJson('/api/v1/translations/da')->assertNotFound();

        $da->update(['is_active' => true]);

        $this->getJson('/api/v1/translations/da')
            ->assertOk()
            ->assertJsonPath('messages', array_merge($this->english(), ['account.log_in' => 'Log ind']));
    }

    // ------------------------------------------------------------ AC-5

    public function test_an_orphan_override_is_in_neither_the_bundle_nor_the_completion_count(): void
    {
        $da = TranslationLocale::factory()->create();
        $this->override($da, 'account.log_in', 'Log ind');
        $this->override($da, 'removed.old_label', 'Gammel tekst');
        $english = $this->english();

        $messages = $this->getJson('/api/v1/translations/da')->assertOk()->json('messages');

        $this->assertArrayNotHasKey('removed.old_label', $messages);
        $this->assertCount(count($english), $messages);

        $completion = app(TranslationEditorServiceInterface::class)->completion('da')->getData();
        $this->assertSame(1, $completion->translated);
        $this->assertSame(count($english), $completion->total);
        $this->assertSame((int) floor(100 / count($english)), $completion->percent);
    }

    // ------------------------------------------------------------ AC-6

    public function test_the_bundle_runs_the_same_queries_for_3_and_30_overrides(): void
    {
        $da = TranslationLocale::factory()->create();
        $keys = array_keys($this->english());
        $this->assertGreaterThanOrEqual(10, count($keys), 'The catalogue is too small for this test.');

        $this->seedOverrides($da, array_slice($keys, 0, 3));
        $few = $this->countQueries(fn () => $this->getJson('/api/v1/translations/da')->assertOk());

        $this->seedOverrides($da, $this->manyKeys($keys, 30));
        $many = $this->countQueries(fn () => $this->getJson('/api/v1/translations/da')->assertOk());

        $this->assertSame($few, $many);
        $this->assertLessThanOrEqual(2, $many, 'One query for the locale and one for its overrides.');
    }

    public function test_completion_runs_the_same_queries_for_3_and_30_overrides(): void
    {
        $da = TranslationLocale::factory()->create();
        $keys = array_keys($this->english());
        $editor = app(TranslationEditorServiceInterface::class);

        $this->seedOverrides($da, array_slice($keys, 0, 3));
        $few = $this->countQueries(fn () => $editor->completion('da'));

        $this->seedOverrides($da, $this->manyKeys($keys, 30));
        $many = $this->countQueries(fn () => $editor->completion('da'));

        $this->assertSame($few, $many);
        $this->assertLessThanOrEqual(2, $many);
    }

    /**
     * 30 override rows even when the catalogue is smaller: the extras are orphans, which cost the
     * same single query as valid rows.
     *
     * @param list<string> $keys
     *
     * @return list<string>
     */
    private function manyKeys(array $keys, int $count): array
    {
        $result = array_slice($keys, 0, $count);

        for ($i = count($result); $i < $count; $i++) {
            $result[] = "orphan.key_{$i}";
        }

        return $result;
    }

    /** @param list<string> $keys */
    private function seedOverrides(TranslationLocale $locale, array $keys): void
    {
        Translation::query()->where('translation_locale_id', $locale->id)->delete();

        foreach ($keys as $key) {
            $this->override($locale, $key, "x {$key}");
        }
    }

    private function countQueries(callable $action): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $action();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
