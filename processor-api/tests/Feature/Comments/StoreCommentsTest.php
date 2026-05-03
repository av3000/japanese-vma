<?php

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ArticleStatus;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Article as PersistenceArticle;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StoreCommentsTest extends TestCase
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
                'id' => ObjectTemplateType::LIST->getLegacyId(),
                'title' => ObjectTemplateType::LIST->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::LIST->value,
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
            [
                'id' => ObjectTemplateType::POST->getLegacyId(),
                'title' => ObjectTemplateType::POST->getTitle(),
                'entity_type_uuid' => ObjectTemplateType::POST->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_authenticated_user_can_create_article_comment_through_generic_v1_route(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => $article->id,
            'entity_uuid' => $article->uuid,
            'content' => 'New v1 article comment.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.entity_uuid', $article->uuid)
            ->assertJsonPath('data.entity_type', 'article')
            ->assertJsonPath('data.author_id', $author->id)
            ->assertJsonPath('data.content', 'New v1 article comment.')
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked_by_viewer', false);

        $this->assertDatabaseHas('comments', [
            'template_id' => ObjectTemplateType::ARTICLE->getLegacyId(),
            'real_object_id' => $article->id,
            'real_object_uuid' => $article->uuid,
            'entity_type_uuid' => ObjectTemplateType::ARTICLE->value,
            'user_id' => $author->id,
            'content' => 'New v1 article comment.',
        ]);

        $this->assertNotNull(PersistenceComment::query()->latest('id')->value('uuid'));
    }

    public function test_authenticated_user_can_create_catalogue_comment_through_generic_v1_route_and_read_it_back(): void
    {
        $author = $this->createUser();
        $catalogue = $this->createCatalogue($author);
        Passport::actingAs($author, ['*'], 'api');

        $createResponse = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::LIST->value,
            'entity_id' => $catalogue->id,
            'entity_uuid' => $catalogue->uuid,
            'content' => 'New v1 catalogue comment.',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.entity_uuid', $catalogue->uuid)
            ->assertJsonPath('data.entity_type', 'list')
            ->assertJsonPath('data.author_id', $author->id)
            ->assertJsonPath('data.content', 'New v1 catalogue comment.');

        $this->assertDatabaseHas('comments', [
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'real_object_uuid' => $catalogue->uuid,
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'user_id' => $author->id,
            'content' => 'New v1 catalogue comment.',
        ]);

        $readResponse = $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/comments");

        $readResponse->assertOk()
            ->assertJsonPath('data.items.0.entity_uuid', $catalogue->uuid)
            ->assertJsonPath('data.items.0.content', 'New v1 catalogue comment.');
    }

    public function test_authenticated_user_can_create_post_comment_through_generic_v1_route(): void
    {
        $author = $this->createUser();
        $post = $this->createPost($author);
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::POST->value,
            'entity_id' => $post->id,
            'entity_uuid' => $post->uuid,
            'content' => 'New v1 post comment.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.entity_uuid', $post->uuid)
            ->assertJsonPath('data.entity_type', 'post')
            ->assertJsonPath('data.author_id', $author->id)
            ->assertJsonPath('data.content', 'New v1 post comment.');

        $this->assertDatabaseHas('comments', [
            'template_id' => ObjectTemplateType::POST->getLegacyId(),
            'real_object_id' => $post->id,
            'real_object_uuid' => $post->uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $author->id,
            'content' => 'New v1 post comment.',
        ]);
    }

    public function test_unsupported_entity_type_returns_validation_error(): void
    {
        $author = $this->createUser();
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => (string) Str::uuid(),
            'entity_id' => 1,
            'entity_uuid' => (string) Str::uuid(),
            'content' => 'Unsupported target.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.entity_type.0', 'The selected entity type is invalid.');
    }

    public function test_entity_id_is_required_for_generic_v1_comment_create(): void
    {
        $author = $this->createUser();
        $article = $this->createArticle($author);
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_uuid' => $article->uuid,
            'content' => 'Missing entity id.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.entity_id.0', 'The entity id field is required.');
    }

    public function test_entity_uuid_must_be_a_valid_uuid_for_generic_v1_comment_create(): void
    {
        $author = $this->createUser();
        Passport::actingAs($author, ['*'], 'api');

        $response = $this->postJson('/api/v1/comments', [
            'entity_type' => ObjectTemplateType::ARTICLE->value,
            'entity_id' => 123,
            'entity_uuid' => 'not-a-uuid',
            'content' => 'Bad uuid.',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.entity_uuid.0', 'The entity uuid field must be a valid UUID.');
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

    private function createCatalogue(User $user, array $overrides = []): Catalogue
    {
        return Catalogue::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => 'Test Catalogue',
            'description' => 'Test description',
            'publicity' => 1,
            'type' => 5,
        ], $overrides));
    }

    private function createPost(User $user, array $overrides = []): object
    {
        $uuid = (string) Str::uuid();

        $id = DB::table('posts')->insertGetId(array_merge([
            'uuid' => $uuid,
            'entity_type_uuid' => ObjectTemplateType::POST->value,
            'user_id' => $user->id,
            'type' => 'discussion',
            'title' => 'Test Post',
            'content' => 'Test post content.',
            'locked' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));

        return (object) [
            'id' => $id,
            'uuid' => $overrides['uuid'] ?? $uuid,
        ];
    }
}
