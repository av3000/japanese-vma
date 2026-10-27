<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Article;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * An article catalogue never shows, or accepts, an article the viewer could not open.
 */
class CatalogueArticleVisibilityTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    private User $catalogueOwner;

    private User $articleAuthor;

    private Catalogue $catalogue;

    private Article $publicArticle;

    private Article $privateArticle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();

        $this->catalogueOwner = User::factory()->create();
        $this->articleAuthor = User::factory()->create();

        $this->catalogue = Catalogue::factory()
            ->byUser($this->catalogueOwner)
            ->ofType(SavedListType::ARTICLES)
            ->create(['publicity' => 1]);

        $this->publicArticle = Article::factory()->byUser($this->articleAuthor)->create(['title_jp' => '公開記事']);
        $this->privateArticle = Article::factory()->byUser($this->articleAuthor)->asPrivate()->create(['title_jp' => '非公開記事']);

        foreach ([$this->publicArticle, $this->privateArticle] as $article) {
            DB::table('customlist_object')->insert([
                'list_id' => $this->catalogue->id,
                'listtype_id' => SavedListType::ARTICLES->value,
                'real_object_id' => $article->id,
            ]);
        }
    }

    public function test_guest_does_not_see_another_users_private_article(): void
    {
        $this->assertSeesOnlyPublicArticle($this->json('GET', $this->showUrl()));
    }

    public function test_catalogue_owner_does_not_see_another_users_private_article(): void
    {
        Passport::actingAs($this->catalogueOwner, ['*'], 'api');

        $this->assertSeesOnlyPublicArticle($this->json('GET', $this->showUrl()));
    }

    public function test_unrelated_user_does_not_see_another_users_private_article(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->assertSeesOnlyPublicArticle($this->json('GET', $this->showUrl()));
    }

    public function test_article_author_sees_their_own_private_article(): void
    {
        Passport::actingAs($this->articleAuthor, ['*'], 'api');

        $response = $this->json('GET', $this->showUrl());

        $response->assertOk()
            ->assertJsonPath('items_count', 2)
            ->assertJsonCount(2, 'items');
        $this->assertEqualsCanonicalizing(
            [$this->publicArticle->uuid, $this->privateArticle->uuid],
            array_column($response->json('items'), 'uuid'),
        );
    }

    public function test_catalogue_list_keeps_the_raw_item_count(): void
    {
        $response = $this->json('GET', '/api/v1/catalogues');

        $response->assertOk();
        $listed = collect($response->json('items'))->firstWhere('uuid', $this->catalogue->uuid);
        $this->assertNotNull($listed);
        $this->assertSame(2, $listed['items_count']);
    }

    public function test_adding_another_users_private_article_is_refused(): void
    {
        $catalogue = Catalogue::factory()
            ->byUser($this->catalogueOwner)
            ->ofType(SavedListType::ARTICLES)
            ->create();
        Passport::actingAs($this->catalogueOwner, ['*'], 'api');

        $response = $this->json('POST', "/api/v1/catalogues/{$catalogue->uuid}/items", [
            'item_id' => $this->privateArticle->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('customlist_object', [
            'list_id' => $catalogue->id,
            'real_object_id' => $this->privateArticle->id,
        ]);
    }

    public function test_adding_a_public_article_or_ones_own_private_article_is_allowed(): void
    {
        $catalogue = Catalogue::factory()
            ->byUser($this->articleAuthor)
            ->ofType(SavedListType::ARTICLES)
            ->create();
        Passport::actingAs($this->articleAuthor, ['*'], 'api');

        foreach ([$this->publicArticle, $this->privateArticle] as $article) {
            $this->json('POST', "/api/v1/catalogues/{$catalogue->uuid}/items", ['item_id' => $article->id])
                ->assertCreated();
        }
    }

    private function showUrl(): string
    {
        return "/api/v1/catalogues/{$this->catalogue->uuid}";
    }

    private function assertSeesOnlyPublicArticle(\Illuminate\Testing\TestResponse $response): void
    {
        $response->assertOk()
            ->assertJsonPath('items_count', 1)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.uuid', $this->publicArticle->uuid)
            ->assertDontSee('非公開記事');
    }
}
