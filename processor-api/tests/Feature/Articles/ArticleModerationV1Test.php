<?php

namespace Tests\Feature\Articles;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class ArticleModerationV1Test extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_pending_queue_requires_authentication(): void
    {
        $this->getJson('/api/v1/articles/pending')
            ->assertUnauthorized();
    }

    public function test_pending_queue_rejects_authenticated_non_admin(): void
    {
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->getJson('/api/v1/articles/pending')
            ->assertForbidden();
    }

    public function test_pending_queue_returns_only_moderatable_statuses_in_deterministic_order(): void
    {
        $this->actingAsAdmin();
        $author = $this->createUser();
        $oldPending = $this->createArticle($author, [
            'title_jp' => 'Old pending',
            'status' => ArticleStatus::PENDING,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $sameTimeReviewing = $this->createArticle($author, [
            'title_jp' => 'Same time reviewing',
            'status' => ArticleStatus::REVIEWING,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $sameTimePending = $this->createArticle($author, [
            'title_jp' => 'Same time pending',
            'status' => ArticleStatus::PENDING,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $this->createArticle($author, ['status' => ArticleStatus::PROCESSED]);
        $this->createArticle($author, ['status' => ArticleStatus::REJECTED]);
        $this->createArticle($author, ['status' => ArticleStatus::APPROVED]);

        $response = $this->getJson('/api/v1/articles/pending');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 3)
            ->assertJsonPath('items.0.uuid', $sameTimePending->uuid)
            ->assertJsonPath('items.1.uuid', $sameTimeReviewing->uuid)
            ->assertJsonPath('items.2.uuid', $oldPending->uuid);
    }

    public function test_pending_queue_returns_lean_items_with_shared_hashtag_shape(): void
    {
        $this->actingAsAdmin();
        $author = $this->createUser();
        $taggedArticle = $this->createArticle($author, ['status' => ArticleStatus::PENDING]);
        $untaggedArticle = $this->createArticle($author, ['status' => ArticleStatus::REVIEWING]);
        $this->attachHashtag($taggedArticle, $author, '#reading');

        $items = $this->getJson('/api/v1/articles/pending')
            ->assertOk()
            ->json('items');

        $taggedItem = collect($items)->firstWhere('uuid', $taggedArticle->uuid);
        $untaggedItem = collect($items)->firstWhere('uuid', $untaggedArticle->uuid);

        $this->assertSame(
            ['uuid', 'title_jp', 'status', 'status_label', 'hashtags', 'created_at'],
            array_keys($taggedItem),
        );
        $this->assertSame('#reading', $taggedItem['hashtags'][0]['content']);
        $this->assertArrayHasKey('id', $taggedItem['hashtags'][0]);
        $this->assertSame([], $untaggedItem['hashtags']);
    }

    public function test_pending_queue_uses_defaults_and_validates_pagination(): void
    {
        $this->actingAsAdmin();
        $author = $this->createUser();

        foreach (range(1, 21) as $number) {
            $this->createArticle($author, [
                'title_jp' => "Pending {$number}",
                'status' => ArticleStatus::PENDING,
            ]);
        }

        $this->getJson('/api/v1/articles/pending')
            ->assertOk()
            ->assertJsonPath('pagination.page', 1)
            ->assertJsonPath('pagination.per_page', 20)
            ->assertJsonPath('pagination.total', 21)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.has_more', true);

        $this->getJson('/api/v1/articles/pending?page=2&per_page=1')
            ->assertOk()
            ->assertJsonPath('pagination.page', 2)
            ->assertJsonPath('pagination.per_page', 1);

        $this->getJson('/api/v1/articles/pending?page=0')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['page']);

        $this->getJson('/api/v1/articles/pending?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_pending_queue_returns_an_empty_paginated_response(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/articles/pending')
            ->assertOk()
            ->assertJsonPath('items', [])
            ->assertJsonPath('pagination.total', 0)
            ->assertJsonPath('pagination.has_more', false);
    }

    public function test_pending_route_resolves_the_admin_moderation_action(): void
    {
        $route = app('router')->getRoutes()->match(
            \Illuminate\Http\Request::create('/api/v1/articles/pending', 'GET'),
        );

        $this->assertSame(
            'App\\Http\\v1\\Articles\\Controllers\\ArticleController@pending',
            $route->getActionName(),
        );
    }

    public function test_status_update_requires_authentication(): void
    {
        $article = $this->createArticle($this->createUser());

        $this->postJson("/api/v1/articles/{$article->uuid}/status", ['status' => ArticleStatus::APPROVED->value])
            ->assertUnauthorized();
    }

    public function test_status_update_rejects_authenticated_non_admin_without_persisting(): void
    {
        $article = $this->createArticle($this->createUser(), ['status' => ArticleStatus::PENDING]);
        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->postJson("/api/v1/articles/{$article->uuid}/status", ['status' => ArticleStatus::APPROVED->value])
            ->assertForbidden();

        $this->assertSame(ArticleStatus::PENDING, $article->fresh()->status);
    }

    public function test_admin_can_persist_each_defined_article_status(): void
    {
        $this->actingAsAdmin();

        foreach (ArticleStatus::cases() as $status) {
            $article = $this->createArticle($this->createUser(), ['status' => ArticleStatus::PENDING]);

            $this->postJson("/api/v1/articles/{$article->uuid}/status", ['status' => $status->value])
                ->assertOk()
                ->assertJsonPath('uuid', $article->uuid)
                ->assertJsonPath('status', $status->value)
                ->assertJsonPath('status_label', $status->label());

            $this->assertSame($status, $article->fresh()->status);
        }
    }

    public function test_status_update_rejects_invalid_payloads_without_persisting(): void
    {
        $this->actingAsAdmin();
        $article = $this->createArticle($this->createUser(), ['status' => ArticleStatus::PENDING]);

        foreach ([[], ['status' => 'approved'], ['status' => 99]] as $payload) {
            $this->postJson("/api/v1/articles/{$article->uuid}/status", $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['status']);

            $this->assertSame(ArticleStatus::PENDING, $article->fresh()->status);
        }
    }

    public function test_status_update_returns_not_found_for_unknown_uuid(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/articles/3fa85f64-5717-4562-b3fc-2c963f66afa6/status', ['status' => ArticleStatus::APPROVED->value])
            ->assertNotFound();
    }

    public function test_status_update_rejects_invalid_uuid_before_controller_execution(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/articles/not-a-uuid/status', ['status' => ArticleStatus::APPROVED->value])
            ->assertNotFound();
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        return $admin;
    }

    private function createArticle(User $author, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::factory()
            ->byUser($author)
            ->create(array_merge([
                'publicity' => PublicityStatus::PUBLIC,
                'status' => ArticleStatus::PENDING,
            ], $overrides));
    }

    private function attachHashtag(PersistenceArticle $article, User $author, string $content): void
    {
        $hashtagId = DB::table('uniquehashtags')->insertGetId([
            'content' => $content,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hashtag_entity')->insert([
            'entity_id' => $article->id,
            'entity_type_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'hashtag_id' => $hashtagId,
            'user_id' => $author->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
