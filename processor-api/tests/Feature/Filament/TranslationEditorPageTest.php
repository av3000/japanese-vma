<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\Pages\Translations\TranslationEditor;
use App\Filament\Pages\Translations\TranslationOverview;
use App\Infrastructure\Persistence\Models\Translation;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\Feature\Filament\Concerns\UsesTranslationCatalogue;
use Tests\TestCase;

/**
 * The translation editor of #664: one label next to its English source, saved live, blank to go
 * back to English.
 */
class TranslationEditorPageTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;
    use UsesTranslationCatalogue;

    private TranslationLocale $da;

    private User $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->useCatalogue([
            'auth.sign_in' => 'Sign in',
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
        ]);

        $this->da = TranslationLocale::query()->create(['code' => 'da', 'name' => 'Dansk', 'is_active' => true]);
        $this->translator = $this->createUserWithPermissions(['translations.view', 'translations.update'], 'Translator');
        $this->actingAsPanelUser($this->translator);
    }

    // AC-1

    public function test_a_save_is_live_in_the_bundle_records_the_translator_and_shows_translated_in_the_overview(): void
    {
        Livewire::test(TranslationEditor::class)
            ->set('locale', 'da')
            ->set('translationKey', 'auth.sign_in')
            ->set('translation', 'Log ind')
            ->call('save')
            ->assertNotified('Saved. Live on the next page load.');

        $messages = $this->bundle();
        $this->assertSame('Log ind', $messages['auth.sign_in']);
        $this->assertSame('Save', $messages['common.save']);

        $stored = Translation::query()->where('key', 'auth.sign_in')->sole();
        $this->assertSame($this->translator->id, $stored->updated_by_user_id);

        Livewire::test(TranslationOverview::class)
            ->set('tableFilters.state.value', 'all')
            ->assertSeeTextInOrder(['auth.sign_in', 'Sign in', 'Log ind', 'Translated'])
            ->set('tableFilters.state.value', 'missing')
            ->assertCanNotSeeTableRecords(['auth.sign_in']);
    }

    public function test_the_editor_opens_on_the_language_and_key_in_the_url_with_the_saved_value_in_the_field(): void
    {
        $this->override('auth.sign_in', 'Log ind');

        Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->assertSet('locale', 'da')
            ->assertSet('translationKey', 'auth.sign_in')
            ->assertSet('translation', 'Log ind')
            ->assertSeeText('Sign in');
    }

    public function test_an_overview_row_links_to_the_editor_for_that_key_and_language(): void
    {
        $url = TranslationEditor::getUrl(['locale' => 'da', 'key' => 'common.save']);

        Livewire::test(TranslationOverview::class)
            ->assertSeeHtml('href="'.e($url).'"');
    }

    // AC-2

    public function test_an_empty_or_whitespace_only_save_deletes_the_override_so_english_returns_and_the_overview_shows_missing(): void
    {
        foreach (['', "  \n "] as $blank) {
            $this->override('auth.sign_in', 'Log ind');

            Livewire::test(TranslationEditor::class)
                ->set('locale', 'da')
                ->set('translationKey', 'auth.sign_in')
                ->set('translation', $blank)
                ->call('save')
                ->assertNotified('Cleared. English shows again, live on the next page load.');

            $this->assertDatabaseMissing('translations', ['key' => 'auth.sign_in']);
            $this->assertSame('Sign in', $this->bundle()['auth.sign_in']);

            Livewire::test(TranslationOverview::class)->assertCanSeeTableRecords(['auth.sign_in']);
        }
    }

    // AC-3

    public function test_an_empty_field_shows_the_english_fallback_preview_and_the_helper_text_and_english_is_never_editable(): void
    {
        Livewire::withQueryParams(['locale' => 'da', 'key' => 'common.cancel'])
            ->test(TranslationEditor::class)
            ->assertSet('translation', '')
            ->assertSeeText('Preview while empty')
            ->assertSeeText('Leave empty to use English')
            ->assertFormFieldExists('translation', fn ($field) => ! $field->isDisabled())
            ->assertFormFieldDoesNotExist('sourceText')
            ->assertFormFieldDoesNotExist('english')
            ->set('translation', 'Annuller')
            ->assertDontSeeText('Preview while empty');
    }

    public function test_english_cannot_be_chosen_and_a_tampered_english_save_writes_nothing(): void
    {
        $component = Livewire::withQueryParams(['locale' => 'en', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->assertSet('locale', 'da');

        $component
            ->set('locale', 'en')
            ->set('translationKey', 'auth.sign_in')
            ->set('translation', 'Hacked')
            ->call('save');

        $this->assertDatabaseCount('translations', 0);
    }

    // AC-4

    public function test_a_language_switched_off_after_the_editor_opened_asks_for_an_active_one_and_writes_nothing(): void
    {
        $component = Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->set('translation', 'Log ind');

        $this->da->update(['is_active' => false]);

        $component
            ->call('save')
            ->assertNotified('This language is no longer active. Choose an active language.')
            ->assertSet('locale', null);

        $this->assertDatabaseCount('translations', 0);
    }

    public function test_a_key_removed_from_english_after_the_editor_opened_is_reported_and_writes_nothing(): void
    {
        $component = Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->set('translation', 'Log ind');

        $this->useCatalogue(['common.save' => 'Save']);

        $component
            ->call('save')
            ->assertNotified('This key no longer exists. Choose another key.')
            ->assertSet('translationKey', null);

        $this->assertDatabaseCount('translations', 0);
    }

    public function test_a_translation_over_2000_characters_is_refused_under_the_field_and_writes_nothing(): void
    {
        Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->set('translation', str_repeat('æ', 2001))
            ->call('save')
            ->assertHasErrors(['translation']);

        $this->assertDatabaseCount('translations', 0);
    }

    // AC-5

    public function test_a_user_who_may_only_view_gets_a_read_only_editor_and_a_403_on_save(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['translations.view'], 'Viewer'));

        Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->assertSuccessful()
            ->assertFormFieldExists('translation', fn ($field) => $field->isDisabled())
            ->assertDontSeeText('Save translation')
            ->set('translation', 'Log ind')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('translations', 0);
    }

    public function test_the_editor_is_forbidden_without_translations_view(): void
    {
        $this->actingAsPanelUser($this->createUserWithPermissions(['translations.update'], 'Updater only'));

        $this->get(TranslationEditor::getUrl())->assertForbidden();
    }

    public function test_markup_is_stored_and_served_as_plain_text_and_never_rendered_as_html(): void
    {
        Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->set('translation', '<b>x</b>')
            ->call('save');

        $this->assertDatabaseHas('translations', ['key' => 'auth.sign_in', 'value' => '<b>x</b>']);
        $this->assertSame('<b>x</b>', $this->bundle()['auth.sign_in']);

        Livewire::withQueryParams(['locale' => 'da', 'key' => 'auth.sign_in'])
            ->test(TranslationEditor::class)
            ->assertSet('translation', '<b>x</b>');

        Livewire::test(TranslationOverview::class)
            ->set('tableFilters.state.value', 'all')
            ->assertSeeText('<b>x</b>')
            ->assertDontSeeHtml('<b>x</b>');
    }

    /**
     * The public `da` bundle. Its keys contain dots, so it is read as a flat map, not a JSON path.
     *
     * @return array<string, string>
     */
    private function bundle(): array
    {
        return $this->getJson('/api/v1/translations/da')->assertOk()->json('messages');
    }

    private function override(string $key, string $value): void
    {
        Translation::query()->create([
            'translation_locale_id' => $this->da->id,
            'key' => $key,
            'value' => $value,
        ]);
    }
}
