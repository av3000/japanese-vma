<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Translations\Interfaces\Sources\EnglishSourceCatalogueInterface;
use App\Filament\Pages\Translations\TranslationOverview;
use App\Infrastructure\Persistence\Models\Translation;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * The translation overview of #663: every English key for one language, opening on Missing.
 */
class TranslationOverviewPageTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private TranslationLocale $da;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->useCatalogue([
            'account.log_in' => 'Log In',
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
            'common.submit' => 'Submit',
        ]);

        $this->da = TranslationLocale::query()->create(['code' => 'da', 'name' => 'Dansk', 'is_active' => true]);
        $this->actingAsPanelUser($this->createUserWithPermissions(['translations.view'], 'Translator'));
    }

    // AC-1

    public function test_all_shows_every_english_key_once_with_its_value_and_a_translated_or_missing_badge(): void
    {
        $this->override('account.log_in', 'Log ind');

        Livewire::test(TranslationOverview::class)
            ->set('tableFilters.state.value', 'all')
            ->assertCanSeeTableRecords(['account.log_in', 'common.save', 'common.cancel', 'common.submit'], inOrder: true)
            ->assertCountTableRecords(4)
            ->assertSeeTextInOrder(['account.log_in', 'Log In', 'Log ind', 'Translated'])
            // The "Missing" option of the Show filter is always on the page, so assert the badge in a row.
            ->assertSeeTextInOrder(['common.save', 'Save', 'Missing']);
    }

    // AC-2

    public function test_search_matches_the_key_the_english_text_or_the_translation_in_any_case(): void
    {
        $this->override('common.save', 'Gem ændringer');

        $page = Livewire::test(TranslationOverview::class)->set('tableFilters.state.value', 'all');

        $page->set('tableSearch', 'ACCOUNT.LOG')->assertCanSeeTableRecords(['account.log_in'])->assertCountTableRecords(1);
        $page->set('tableSearch', 'cANCEL')->assertCanSeeTableRecords(['common.cancel'])->assertCountTableRecords(1);
        $page->set('tableSearch', 'ÆNDRINGER')->assertCanSeeTableRecords(['common.save'])->assertCountTableRecords(1);
    }

    // AC-3

    public function test_it_opens_on_missing_and_all_lists_every_key(): void
    {
        $this->override('account.log_in', 'Log ind');
        $this->override('common.save', '   ');

        Livewire::test(TranslationOverview::class)
            ->assertSet('tableFilters.state.value', 'missing')
            ->assertCanSeeTableRecords(['common.save', 'common.cancel', 'common.submit'])
            ->assertCanNotSeeTableRecords(['account.log_in'])
            ->set('tableFilters.state.value', 'all')
            ->assertCountTableRecords(4);
    }

    public function test_the_language_select_offers_active_non_english_languages_only(): void
    {
        TranslationLocale::query()->create(['code' => 'sv', 'name' => 'Svenska', 'is_active' => false]);
        TranslationLocale::query()->create(['code' => 'de', 'name' => 'Deutsch', 'is_active' => true]);
        $this->override('account.log_in', 'Log ind');

        Livewire::test(TranslationOverview::class, ['locale' => 'de'])
            ->assertSet('locale', 'de')
            ->assertSee('Deutsch (de)')
            ->assertSee('Dansk (da)')
            ->assertDontSee('Svenska')
            ->assertDontSee('English (en)')
            ->set('locale', 'da')
            ->set('tableFilters.state.value', 'all')
            ->assertSeeText('Log ind');
    }

    public function test_a_language_that_cannot_be_translated_falls_back_to_the_first_one(): void
    {
        Livewire::test(TranslationOverview::class, ['locale' => 'en'])->assertSet('locale', 'da');
        Livewire::test(TranslationOverview::class, ['locale' => 'xx'])->assertSet('locale', 'da');
    }

    public function test_a_language_switched_off_while_the_page_is_open_falls_back_to_the_first_one_without_an_error(): void
    {
        $de = TranslationLocale::query()->create(['code' => 'de', 'name' => 'Deutsch', 'is_active' => true]);

        $page = Livewire::test(TranslationOverview::class, ['locale' => 'de'])->assertSet('locale', 'de');

        $de->update(['is_active' => false]);

        $page->call('$refresh')
            ->assertSet('locale', 'da')
            ->assertNotNotified()
            ->assertSeeText('0 of 4 translated');
    }

    public function test_the_list_is_paginated_and_a_tampered_page_size_falls_back_to_the_default(): void
    {
        $this->useCatalogue(array_combine(
            array_map(fn (int $i): string => sprintf('bulk.key_%02d', $i), range(1, 60)),
            array_map(fn (int $i): string => "Label {$i}", range(1, 60)),
        ));

        $page = Livewire::test(TranslationOverview::class)
            ->set('tableFilters.state.value', 'all')
            ->set('tableRecordsPerPage', 25)
            ->assertCountTableRecords(60)
            ->assertCanSeeTableRecords(['bulk.key_01', 'bulk.key_25'])
            ->assertCanNotSeeTableRecords(['bulk.key_26']);

        $page->call('gotoPage', 2, 'page')
            ->assertCanSeeTableRecords(['bulk.key_26', 'bulk.key_50'])
            ->assertCanNotSeeTableRecords(['bulk.key_25', 'bulk.key_51']);

        $page->call('gotoPage', 3, 'page')
            ->assertCanSeeTableRecords(['bulk.key_51', 'bulk.key_60'])
            ->assertCanNotSeeTableRecords(['bulk.key_50']);

        $page->set('tableRecordsPerPage', 0)->call('gotoPage', 1, 'page')->assertOk()->assertCanSeeTableRecords(['bulk.key_50']);
    }

    // AC-4

    public function test_the_header_counts_valid_translations_and_ignores_an_orphan(): void
    {
        $this->override('account.log_in', 'Log ind');
        $this->override('gone.key', 'Orphan');

        Livewire::test(TranslationOverview::class)
            ->assertSeeText('1 of 4 translated · 25% complete');
    }

    // AC-5

    public function test_the_page_is_forbidden_without_translations_view(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin/translations')
            ->assertForbidden();
    }

    public function test_the_page_opens_for_a_user_with_translations_view(): void
    {
        $this->get('/admin/translations')->assertOk();
    }

    public function test_the_page_runs_the_same_number_of_queries_for_3_and_30_keys(): void
    {
        $queriesFor = function (int $keys): int {
            $this->useCatalogue(array_combine(
                array_map(fn (int $i): string => "bulk.key_{$i}", range(1, $keys)),
                array_map(fn (int $i): string => "Label {$i}", range(1, $keys)),
            ));
            Translation::query()->delete();

            foreach (range(1, intdiv($keys, 3)) as $i) {
                $this->override("bulk.key_{$i}", "Mærkat {$i}");
            }

            // One throwaway render first: the permission and panel caches fill on the first request
            // of a process, and that fixed cost is not what the assertion is about.
            Livewire::test(TranslationOverview::class);

            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test(TranslationOverview::class)->set('tableFilters.state.value', 'all')->assertOk();
            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $count;
        };

        $this->assertSame($queriesFor(3), $queriesFor(30));
    }

    /**
     * @param array<string, string> $messages
     */
    private function useCatalogue(array $messages): void
    {
        $this->app->instance(EnglishSourceCatalogueInterface::class, new class($messages) implements EnglishSourceCatalogueInterface
        {
            /** @param array<string, string> $messages */
            public function __construct(private readonly array $messages)
            {
            }

            public function messages(): array
            {
                return $this->messages;
            }

            public function find(string $key): ?string
            {
                return $this->messages[$key] ?? null;
            }
        });
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
