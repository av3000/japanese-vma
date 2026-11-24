<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Translations\Services\TranslationLocaleAdminServiceInterface;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\TranslationLocales\Pages\ManageTranslationLocales;
use App\Filament\Support\PanelActor;
use App\Infrastructure\Persistence\Models\TranslationLocale;
use App\Infrastructure\Persistence\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * The language list is a simple resource: one page, with create and edit as modal actions on it.
 */
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
        Livewire::test(ManageTranslationLocales::class)
            ->callAction('create', ['code' => 'da', 'name' => 'Dansk', 'is_active' => true])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk', 'is_active' => true]);

        $this->getJson('/api/v1/translations/locales')
            ->assertOk()
            ->assertJsonPath('items.1.code', 'da');
    }

    public function test_a_language_created_without_the_flag_stays_off_the_public_list(): void
    {
        Livewire::test(ManageTranslationLocales::class)
            ->callAction('create', ['code' => 'sv', 'name' => 'Svenska'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'sv', 'is_active' => false]);
        $this->getJson('/api/v1/translations/locales')->assertJsonCount(1, 'items');
    }

    public function test_the_toggle_activates_and_deactivates_and_the_public_list_follows(): void
    {
        $sv = TranslationLocale::factory()->code('sv', 'Svenska')->inactive()->create();

        Livewire::test(ManageTranslationLocales::class)
            ->assertCanSeeTableRecords([$sv])
            ->call('updateTableColumnState', 'is_active', (string) $sv->getKey(), true);

        $this->getJson('/api/v1/translations/locales')->assertJsonPath('items.1.code', 'sv');

        Livewire::test(ManageTranslationLocales::class)
            ->call('updateTableColumnState', 'is_active', (string) $sv->getKey(), false);

        $this->getJson('/api/v1/translations/locales')->assertJsonCount(1, 'items');
    }

    public function test_the_toggle_does_not_write_back_a_name_changed_after_the_table_was_drawn(): void
    {
        $da = TranslationLocale::factory()->create();

        $table = Livewire::test(ManageTranslationLocales::class)->assertCanSeeTableRecords([$da]);

        // Another administrator renames the language after this table was rendered.
        TranslationLocale::query()->whereKey($da->getKey())->update(['name' => 'Dansk (DK)']);

        $table->call('updateTableColumnState', 'is_active', (string) $da->getKey(), false);

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk (DK)', 'is_active' => false]);
    }

    public function test_the_name_and_flag_can_be_edited(): void
    {
        $da = TranslationLocale::factory()->create();

        Livewire::test(ManageTranslationLocales::class)
            ->mountAction(TestAction::make('edit')->table($da))
            ->assertActionDataSet(['code' => 'da', 'name' => 'Dansk', 'is_active' => true])
            ->setActionData(['name' => 'Dansk (DK)', 'is_active' => false])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk (DK)', 'is_active' => false]);
    }

    // AC-3

    public function test_english_cannot_be_deactivated_through_the_toggle(): void
    {
        $en = TranslationLocale::query()->where('code', 'en')->firstOrFail();

        Livewire::test(ManageTranslationLocales::class)
            ->call('updateTableColumnState', 'is_active', (string) $en->getKey(), false);

        $this->assertDatabaseHas('translation_locales', ['code' => 'en', 'is_active' => true]);
    }

    public function test_a_crafted_edit_cannot_deactivate_english_or_change_its_code(): void
    {
        $en = TranslationLocale::query()->where('code', 'en')->firstOrFail();

        Livewire::test(ManageTranslationLocales::class)
            ->callAction(TestAction::make('edit')->table($en), ['code' => 'xx', 'is_active' => false, 'name' => 'English']);

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

        Livewire::test(ManageTranslationLocales::class)
            ->mountAction(TestAction::make('edit')->table($da))
            ->assertFormFieldDisabled('code')
            ->setActionData(['code' => 'sv'])
            ->callMountedAction();

        $this->assertDatabaseHas('translation_locales', ['code' => 'da']);
        $this->assertDatabaseMissing('translation_locales', ['code' => 'sv']);
    }

    // AC-4

    public function test_a_code_that_is_not_two_lowercase_letters_fails_with_a_message_in_the_modal(): void
    {
        foreach (['DA', 'd', 'dan', 'pt-BR', 'd1'] as $code) {
            Livewire::test(ManageTranslationLocales::class)
                ->callAction('create', ['code' => $code, 'name' => 'Whatever'])
                ->assertHasActionErrors(['code'])
                ->assertActionMounted('create');
        }

        $this->assertDatabaseCount('translation_locales', 1);
    }

    public function test_a_code_that_already_exists_fails_with_a_message_in_the_modal(): void
    {
        TranslationLocale::factory()->create();

        foreach (['da', 'en'] as $code) {
            Livewire::test(ManageTranslationLocales::class)
                ->callAction('create', ['code' => $code, 'name' => 'Duplicate'])
                ->assertHasActionErrors(['code'])
                ->assertActionMounted('create');
        }

        $this->assertDatabaseCount('translation_locales', 2);
    }

    public function test_a_blank_name_on_edit_fails_with_a_message_in_the_modal(): void
    {
        $da = TranslationLocale::factory()->create();

        Livewire::test(ManageTranslationLocales::class)
            ->callAction(TestAction::make('edit')->table($da), ['name' => "\u{3000}"])
            ->assertHasActionErrors(['name']);

        $this->assertDatabaseHas('translation_locales', ['code' => 'da', 'name' => 'Dansk']);
    }

    public function test_create_and_edit_have_no_pages_of_their_own(): void
    {
        $da = TranslationLocale::factory()->create();

        $this->get('/admin/translation-locales')->assertOk();
        $this->get('/admin/translation-locales/create')->assertNotFound();
        $this->get("/admin/translation-locales/{$da->getKey()}/edit")->assertNotFound();
    }

    // AC-5

    public function test_a_dashboard_only_user_gets_403_on_the_resource_page(): void
    {
        $dashboardOnly = $this->createUserWithPermissions(['operations.view'], 'Dashboard Only');
        $this->actingAsPanelUser($dashboardOnly);

        $this->get('/admin')->assertOk();
        $this->get('/admin/translation-locales')->assertForbidden();
    }

    public function test_a_dashboard_only_user_cannot_drive_the_create_and_edit_actions(): void
    {
        $dashboardOnly = $this->createUserWithPermissions(['operations.view'], 'Dashboard Only');
        $this->actingAsPanelUser($dashboardOnly);

        Livewire::test(ManageTranslationLocales::class)->assertForbidden();

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

        Livewire::test(ManageTranslationLocales::class)
            ->assertActionDoesNotExist(TestAction::make('delete')->table($da))
            ->mountAction(TestAction::make('edit')->table($da))
            ->assertActionDoesNotExist('delete');
    }
}
