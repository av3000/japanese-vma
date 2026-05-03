<?php

namespace Tests\Feature\Comments;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\Comment as PersistenceComment;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GetListCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
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
        ]);
    }

    public function test_guest_can_fetch_list_comments_by_catalogue_uuid(): void
    {
        $author = $this->createUser();
        $catalogue = $this->createCatalogue($author);
        $comment = $this->createComment($catalogue, $author);

        $response = $this->getJson("/api/v1/catalogues/{$catalogue->uuid}/comments");

        $response->assertOk()
            ->assertJsonPath('data.items.0.id', $comment->id)
            ->assertJsonPath('data.items.0.entity_uuid', $catalogue->uuid)
            ->assertJsonPath('data.items.0.likes_count', 0)
            ->assertJsonPath('data.items.0.is_liked_by_viewer', false);
    }

    public function test_unknown_catalogue_uuid_returns_not_found_for_list_comments(): void
    {
        $response = $this->getJson('/api/v1/catalogues/'.Str::uuid().'/comments');

        $response->assertNotFound()
            ->assertJsonPath('title', 'Catalogue not found');
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

    private function createComment(Catalogue $catalogue, User $user, array $overrides = []): PersistenceComment
    {
        return PersistenceComment::create(array_merge([
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'real_object_uuid' => $catalogue->uuid,
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'user_id' => $user->id,
            'parent_comment_id' => null,
            'content' => 'Test list comment content.',
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }
}
