<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Shared\Enums\SavedListType;
use App\Filament\Resources\Catalogues\Pages\ListCatalogues;
use App\Filament\Resources\Catalogues\Pages\ViewCatalogue;
use App\Filament\Resources\Catalogues\RelationManagers\ItemsRelationManager;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\CatalogueItem;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Livewire\Livewire;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

class CatalogueResourceTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    private User $admin;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        $this->admin = $this->createAdmin();
        $this->owner = $this->createPanelUser('List Owner');
        $this->actingAsPanelUser($this->admin);
    }

    public function test_catalogues_list_search_and_filters(): void
    {
        $public = $this->catalogue(['title' => 'Grammar picks', 'description' => 'Useful particles']);
        $private = Catalogue::factory()->asPrivate()->create(['user_id' => $this->owner->id, 'title' => 'Private stash']);
        $known = Catalogue::factory()->ofType(SavedListType::KNOWNKANJIS)->create(['user_id' => $this->owner->id, 'title' => 'Known Kanji']);
        $this->item($public, Article::factory()->create(['user_id' => $this->owner->id]));

        Livewire::test(ListCatalogues::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$public, $private, $known]);

        Livewire::test(ListCatalogues::class)
            ->searchTable('particles')
            ->assertCanSeeTableRecords([$public])
            ->assertCanNotSeeTableRecords([$private]);

        Livewire::test(ListCatalogues::class)
            ->filterTable('type', SavedListType::KNOWNKANJIS->value)
            ->assertCanSeeTableRecords([$known])
            ->assertCanNotSeeTableRecords([$public]);

        Livewire::test(ListCatalogues::class)
            ->filterTable('publicity', false)
            ->assertCanSeeTableRecords([$private])
            ->assertCanNotSeeTableRecords([$public]);

        Livewire::test(ListCatalogues::class)
            ->filterTable('empty')
            ->assertCanSeeTableRecords([$private, $known])
            ->assertCanNotSeeTableRecords([$public]);
    }

    public function test_view_page_lists_items_with_labels_resolved_in_one_query(): void
    {
        $catalogue = $this->catalogue(['title' => 'Reading list']);
        $articles = Article::factory()->count(6)->create(['user_id' => $this->owner->id]);
        foreach ($articles as $article) {
            $this->item($catalogue, $article);
        }
        $articles[0]->forceFill(['title_jp' => '最初の記事'])->save();

        Livewire::test(ViewCatalogue::class, ['record' => $catalogue->uuid])
            ->assertOk()
            ->assertSee('Reading list')
            ->assertSee('List Owner');

        $component = Livewire::test(ItemsRelationManager::class, ['ownerRecord' => $catalogue, 'pageClass' => ViewCatalogue::class]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $component->call('$refresh')->assertSee('最初の記事');
        $articleQueries = collect(DB::getQueryLog())
            ->filter(static fn (array $query): bool => str_contains($query['query'], 'from "articles"'))
            ->count();
        DB::disableQueryLog();

        self::assertSame(1, $articleQueries);
    }

    public function test_publicity_toggle_goes_through_the_service_and_is_recorded(): void
    {
        $catalogue = $this->catalogue();

        Livewire::test(ViewCatalogue::class, ['record' => $catalogue->uuid])
            ->callAction('togglePublicity')
            ->assertNotified('The catalogue is now private.');

        self::assertFalse($catalogue->fresh()->publicity);

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CataloguePublicityChanged, $row->action);
        self::assertSame(['publicity' => 'Public'], $row->changes['before']);
    }

    public function test_delete_runs_the_service_cleanup_and_is_recorded(): void
    {
        $catalogue = $this->catalogue(['title' => 'To remove']);
        $this->item($catalogue, Article::factory()->create(['user_id' => $this->owner->id]));

        Livewire::test(ViewCatalogue::class, ['record' => $catalogue->uuid])
            ->callAction('deleteCatalogue')
            ->assertNotified('Catalogue deleted.')
            ->assertRedirect();

        self::assertNull($catalogue->fresh());
        self::assertSame(0, CatalogueItem::query()->where('list_id', $catalogue->id)->count());

        $row = AdminActivity::query()->sole();
        self::assertSame(ActivityAction::CatalogueDeleted, $row->action);
        self::assertSame('To remove', $row->changes['before']['title']);
        self::assertSame(['items' => 1], $row->context);
    }

    public function test_known_lists_offer_no_delete_to_a_moderator(): void
    {
        $known = Catalogue::factory()->ofType(SavedListType::KNOWNWORDS)->create(['user_id' => $this->owner->id]);

        Livewire::test(ViewCatalogue::class, ['record' => $known->uuid])
            ->assertOk()
            ->assertActionHidden('deleteCatalogue')
            ->assertActionVisible('togglePublicity');
    }

    public function test_admin_api_delete_follows_the_same_rule(): void
    {
        $custom = $this->catalogue();
        $known = Catalogue::factory()->ofType(SavedListType::KNOWNWORDS)->create(['user_id' => $this->owner->id]);
        Passport::actingAs($this->admin, ['*'], 'api');

        $this->deleteJson("/api/v1/catalogues/{$known->uuid}")->assertForbidden();
        $this->deleteJson("/api/v1/catalogues/{$custom->uuid}")->assertSuccessful();

        self::assertNotNull($known->fresh());
        self::assertNull($custom->fresh());
    }

    public function test_viewers_get_no_actions_and_others_get_no_access(): void
    {
        $catalogue = $this->catalogue();
        $this->actingAsPanelUser($this->createUserWithPermissions(['catalogues.view']));

        Livewire::test(ViewCatalogue::class, ['record' => $catalogue->uuid])
            ->assertOk()
            ->assertActionHidden('togglePublicity')
            ->assertActionHidden('deleteCatalogue');

        $this->actingAs($this->createUserWithPermissions(['posts.view']), 'web')
            ->get('/admin/catalogues')
            ->assertForbidden();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function catalogue(array $attributes = []): Catalogue
    {
        return Catalogue::factory()->ofType(SavedListType::ARTICLES)->create(['user_id' => $this->owner->id, ...$attributes]);
    }

    private function item(Catalogue $catalogue, Article $article): CatalogueItem
    {
        return CatalogueItem::query()->create([
            'list_id' => $catalogue->id,
            'real_object_id' => $article->id,
            'listtype_id' => (string) SavedListType::ARTICLES->value,
        ]);
    }
}
