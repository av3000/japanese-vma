<?php

declare(strict_types=1);

namespace Tests\Feature\Broadcasting;

use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

/**
 * Issue #252 (ADR 0002 point 4): only users who may view an article may listen to its
 * processing channel.
 */
class ArticleProcessingChannelAuthTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();

        // The `log` and `null` broadcasters authorise every channel unconditionally; only a
        // real driver runs the callbacks in routes/channels.php. Reverb speaks the Pusher
        // auth protocol and needs nothing but an app key and secret to sign the response.
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app',
            'broadcasting.connections.reverb.options.host' => 'localhost',
            'broadcasting.connections.reverb.options.port' => 8081,
            'broadcasting.connections.reverb.options.scheme' => 'http',
            'broadcasting.connections.reverb.options.useTLS' => false,
        ]);

        // Channel definitions are registered on the driver that was active at boot (`log`),
        // so the freshly resolved reverb driver must load them again.
        require base_path('routes/channels.php');
    }

    public function test_owner_of_a_private_article_is_authorised(): void
    {
        $owner = User::factory()->create();
        $article = $this->createArticle($owner, PublicityStatus::PRIVATE);

        Passport::actingAs($owner, ['*'], 'api');

        $this->authorise($article->uuid)->assertOk();
    }

    public function test_another_user_is_refused_on_a_private_article(): void
    {
        $article = $this->createArticle(User::factory()->create(), PublicityStatus::PRIVATE);

        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->authorise($article->uuid)->assertForbidden();
    }

    public function test_another_user_is_authorised_on_a_public_article(): void
    {
        $article = $this->createArticle(User::factory()->create(), PublicityStatus::PUBLIC);

        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->authorise($article->uuid)->assertOk();
    }

    public function test_admin_is_authorised_on_a_private_article(): void
    {
        $article = $this->createArticle(User::factory()->create(), PublicityStatus::PRIVATE);
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::ADMIN->value);

        Passport::actingAs($admin, ['*'], 'api');

        $this->authorise($article->uuid)->assertOk();
    }

    public function test_anonymous_request_is_unauthenticated(): void
    {
        $article = $this->createArticle(User::factory()->create(), PublicityStatus::PUBLIC);

        $this->authorise($article->uuid)->assertUnauthorized();
    }

    public function test_unknown_or_malformed_uuid_is_refused(): void
    {
        Passport::actingAs(User::factory()->create(), ['*'], 'api');

        $this->authorise((string) Str::uuid())->assertForbidden();
        $this->authorise('not-a-uuid')->assertForbidden();
    }

    public function test_the_legacy_per_user_channel_is_gone(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['*'], 'api');

        $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-App.User.{$user->id}",
        ])->assertForbidden();
    }

    private function authorise(string $uuid): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => "private-last_operations.{$uuid}",
        ]);
    }

    private function createArticle(User $owner, PublicityStatus $publicity): PersistenceArticle
    {
        return PersistenceArticle::factory()->byUser($owner)->create([
            'title_jp' => '学校の話',
            'content_jp' => '学校で勉強します。日本語の本文です。',
            'publicity' => $publicity,
        ]);
    }
}
