<?php

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetArticleCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            [
                'id' => ObjectTemplateType::ARTICLE->getLegacyId(),
                'title' => ObjectTemplateType::ARTICLE->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => ObjectTemplateType::COMMENT->getLegacyId(),
                'title' => ObjectTemplateType::COMMENT->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::COMMENT->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_guest_can_fetch_article_comments_with_safe_viewer_defaults(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);
        $comment = $this->createComment($article, $author);

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments");

        $response->assertOk()
            ->assertJsonPath('data.items.0.id', $comment->id)
            ->assertJsonPath('data.items.0.likes_count', 0)
            ->assertJsonPath('data.items.0.is_liked_by_viewer', false);
    }

    public function test_authenticated_viewer_gets_personalized_like_state_for_article_comments(): void
    {
        $author = $this->createUser(['email' => Str::uuid().'@author.example']);
        $viewer = $this->createUser(['email' => Str::uuid().'@viewer.example']);
        $article = $this->createArticle($author);
        $comment = $this->createComment($article, $author);

        Like::create([
            'user_id' => $viewer->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => true,
        ]);

        Passport::actingAs($viewer, ['*'], 'api');

        $response = $this->getJson("/api/v1/articles/{$article->uuid}/comments");

        $response->assertOk()
            ->assertJsonPath('data.items.0.id', $comment->id)
            ->assertJsonPath('data.items.0.likes_count', 1)
            ->assertJsonPath('data.items.0.is_liked_by_viewer', true);
    }

    public function test_guest_cannot_post_article_comments_to_protected_route(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'Guest comment attempt.',
        ]);

        $response->assertStatus(401);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }

    private function createArticle(User $user, array $overrides = []): PersistenceArticle
    {
        return PersistenceArticle::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'title_jp' => 'Japanese title',
            'title_en' => 'English title',
            'content_jp' => 'Japanese content text.',
            'content_en' => 'English content text.',
            'source_link' => 'https://example.com/source',
            'publicity' => PublicityStatus::PUBLIC,
            'status' => ArticleStatus::APPROVED,
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ], $overrides));
    }

    private function createComment(PersistenceArticle $article, User $user, array $overrides = []): PersistenceComment
    {
        return PersistenceComment::create(array_merge([
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $user->id,
            'parent_comment_id' => null,
            'content' => 'Test comment content.',
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }
}
