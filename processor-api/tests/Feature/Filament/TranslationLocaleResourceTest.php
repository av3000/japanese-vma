<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\TranslationLocales\Pages\CreateTranslationLocale;
use App\Filament\Resources\TranslationLocales\Pages\EditTranslationLocale;
use App\Filament\Resources\TranslationLocales\Pages\ListTranslationLocales;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class TranslationLocaleResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->manager = $this->createUserWithPermissions(['locales.manage'], 'Locale Manager');
        $this->actingAsPanelUser($this->manager);
    }

    // AC-1

    public function test_the_role_editor_offers_the_translations_group_and_saves_its_permissions(): void
    {
        $this->actingAsPanelUser($this->createAdmin());

        Livewire::test(CreateRole::class)
            ->assertSchemaComponentExists('permission-group-translations')
            ->fillForm([
                'name' => 'translator',
                'guard_name' => 'api',
                'permission_groups.translations' => ['translations.view', 'translations.update', 'locales.manage'],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        self::assertSame(
            ['locales.manage', 'translations.update', 'translations.view'],
            Role::findByName('translator', 'api')->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    // AC-2

    public function test_a_manager_creates_an_active_language_and_the_public_list_shows_it_at_once(): void
    {
        Livewire::test(CreateTranslationLocale::class)
            ->fillForm(['code' => 'da', 'name' => 'Dansk', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk', 'is_active' => true]);

        $this->getJson('/api/v1/translations/locales')
            ->assertOk()
            ->assertJsonPath('items.1.code', 'da');
    }

    public function test_a_language_created_without_the_flag_stays_off_the_public_list(): void
    {
        Livewire::test(CreateTranslationLocale::class)
            ->fillForm(['code' => 'sv', 'name' => 'Svenska'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'sv', 'is_active' => false]);
        $this->getJson('/api/v1/translations/locales')->assertJsonCount(1, 'items');
    }

    public function test_the_toggle_activates_and_deactivates_and_the_public_list_follows(): void
    {
        $sv = TranslationLocale::factory()->code('sv', 'Svenska')->inactive()->create();

        Livewire::test(ListTranslationLocales::class)
            ->assertCanSeeTableRecords([$sv])
            ->call('updateTableColumnState', 'is_active', (string) $sv->getKey(), true);

        $this->getJson('/api/v1/translations/locales')->assertJsonPath('items.1.code', 'sv');

        Livewire::test(ListTranslationLocales::class)
            ->call('updateTableColumnState', 'is_active', (string) $sv->getKey(), false);

        $this->getJson('/api/v1/translations/locales')->assertJsonCount(1, 'items');
    }

    public function test_the_toggle_does_not_write_back_a_name_changed_after_the_table_was_drawn(): void
    {
        $da = TranslationLocale::factory()->create();

        $table = Livewire::test(ListTranslationLocales::class)->assertCanSeeTableRecords([$da]);

        // Another administrator renames the language after this table was rendered.
        TranslationLocale::query()->whereKey($da->getKey())->update(['name' => 'Dansk (DK)']);

        $table->call('updateTableColumnState', 'is_active', (string) $da->getKey(), false);

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk (DK)', 'is_active' => false]);
    }

    public function test_the_name_and_flag_can_be_edited(): void
    {
        $da = TranslationLocale::factory()->create();

        Livewire::test(EditTranslationLocale::class, ['record' => $da->getKey()])
            ->assertFormSet(['code' => 'da', 'name' => 'Dansk', 'is_active' => true])
            ->fillForm(['name' => 'Dansk (DK)', 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk (DK)', 'is_active' => false]);
    }

    // AC-3

    public function test_english_cannot_be_deactivated_through_the_toggle(): void
    {
        $en = TranslationLocale::query()->where('code', 'en')->firstOrFail();

        Livewire::test(ListTranslationLocales::class)
            ->call('updateTableColumnState', 'is_active', (string) $en->getKey(), false);

        $this->assertDatabaseHas('translation_locales', ['code' => 'en', 'is_active' => true]);
    }

    public function test_a_crafted_edit_cannot_deactivate_english_or_change_its_code(): void
    {
        $en = TranslationLocale::query()->where('code', 'en')->firstOrFail();

        Livewire::test(EditTranslationLocale::class, ['record' => $en->getKey()])
            ->set('data.code', 'xx')
            ->set('data.is_active', false)
            ->set('data.name', 'English')
            ->call('save');

        $this->assertDatabaseHas('translation_locales', ['code' => 'en', 'is_active' => true]);
        $this->assertDatabaseMissing('translation_locales', ['code' => 'xx']);
    }

    public function test_the_service_refuses_to_deactivate_english_for_a_crafted_call(): void
    {
        $result = app(TranslationLocaleAdminServiceInterface::class)
            ->update('en', 'English', false, PanelActor::current());

        self::assertSame('Translations.SourceLocaleLocked', $result->getError()->code);
        $this->assertDatabaseHas('translation_locales', ['code' => 'en', 'is_active' => true]);
    }

    public function test_the_code_is_not_editable_on_the_edit_form(): void
    {
        $da = TranslationLocale::factory()->create();

        Livewire::test(EditTranslationLocale::class, ['record' => $da->getKey()])
            ->assertFormFieldDisabled('code')
            ->set('data.code', 'sv')
            ->call('save');

        $this->assertDatabaseHas('translation_locales', ['code' => 'da']);
        $this->assertDatabaseMissing('translation_locales', ['code' => 'sv']);
    }

    // AC-4

    public function test_a_code_that_is_not_two_lowercase_letters_fails_with_a_message(): void
    {
        foreach (['DA', 'd', 'dan', 'pt-BR', 'd1'] as $code) {
            Livewire::test(CreateTranslationLocale::class)
                ->fillForm(['code' => $code, 'name' => 'Whatever'])
                ->call('create')
                ->assertHasFormErrors(['code']);
        }

        $this->assertDatabaseCount('translation_locales', 1);
    }

    public function test_a_code_that_already_exists_fails_with_a_message(): void
    {
        TranslationLocale::factory()->create();

        foreach (['da', 'en'] as $code) {
            Livewire::test(CreateTranslationLocale::class)
                ->fillForm(['code' => $code, 'name' => 'Duplicate'])
                ->call('create')
                ->assertHasFormErrors(['code']);
        }

        $this->assertDatabaseCount('translation_locales', 2);
    }

    // AC-5

    public function test_a_dashboard_only_user_gets_403_on_the_resource_pages(): void
    {
        $dashboardOnly = $this->createUserWithPermissions(['operations.view'], 'Dashboard Only');
        $da = TranslationLocale::factory()->create();
        $this->actingAsPanelUser($dashboardOnly);

        $this->get('/admin')->assertOk();
        $this->get('/admin/translation-locales')->assertForbidden();
        $this->get('/admin/translation-locales/create')->assertForbidden();
        $this->get("/admin/translation-locales/{$da->getKey()}/edit")->assertForbidden();
    }

    public function test_a_dashboard_only_user_cannot_drive_the_create_and_edit_actions(): void
    {
        $dashboardOnly = $this->createUserWithPermissions(['operations.view'], 'Dashboard Only');
        $da = TranslationLocale::factory()->create();
        $this->actingAsPanelUser($dashboardOnly);

        Livewire::test(CreateTranslationLocale::class)->assertForbidden();
        Livewire::test(EditTranslationLocale::class, ['record' => $da->getKey()])->assertForbidden();
        Livewire::test(ListTranslationLocales::class)->assertForbidden();

        $result = app(TranslationLocaleAdminServiceInterface::class)
            ->create('sv', 'Svenska', true, PanelActor::current());

        self::assertSame('Translations.ManageLocalesDenied', $result->getError()->code);
        $this->assertDatabaseMissing('translation_locales', ['code' => 'sv']);
    }

    public function test_translation_view_and_update_alone_do_not_open_the_resource(): void
    {
        $editor = $this->createUserWithPermissions(['translations.view', 'translations.update'], 'Translator');
        $this->actingAsPanelUser($editor);

        $this->get('/admin/translation-locales')->assertForbidden();
    }

    public function test_there_is_no_delete_action(): void
    {
        $da = TranslationLocale::factory()->create();

        Livewire::test(ListTranslationLocales::class)->assertTableActionDoesNotExist('delete', record: $da);
        Livewire::test(EditTranslationLocale::class, ['record' => $da->getKey()])->assertActionDoesNotExist('delete');
    }
}
