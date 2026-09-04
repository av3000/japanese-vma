<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue as PersistenceCatalogue;
use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\Download;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Like;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class StoreUpdateCatalogueTest extends TestCase
{
    use RefreshDatabase, SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createCatalogue(User $user, array $overrides = []): PersistenceCatalogue
    {
        return PersistenceCatalogue::factory()->byUser($user)->create(array_merge([
            'title' => 'Original Catalogue',
            'description' => 'Original description',
            'publicity' => false,
            'type' => 5,
        ], $overrides));
    }

    /**
     * @param array<string> $tags
     */
    private function attachHashtags(PersistenceCatalogue $catalogue, array $tags): void
    {
        foreach ($tags as $tag) {
            $uniqueTag = Uniquehashtag::firstOrCreate(['content' => $tag]);

            HashtagEntity::create([
                'entity_type_id' => ObjectTemplateType::LIST->getLegacyId(),
                'entity_id' => $catalogue->id,
                'hashtag_id' => $uniqueTag->id,
                'user_id' => $catalogue->user_id,
            ]);
        }
    }

    /**
     * @return array<string>
     */
    private function getHashtagContents(PersistenceCatalogue $catalogue): array
    {
        return HashtagEntity::with('uniquehashtag')
            ->where('entity_type_id', ObjectTemplateType::LIST->getLegacyId())
            ->where('entity_id', $catalogue->id)
            ->get()
            ->map(fn (HashtagEntity $link) => $link->uniquehashtag->content)
            ->values()
            ->all();
    }

    public function test_store_creates_catalogue_for_current_user_with_hashtags_and_initial_view(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('POST', '/api/v1/catalogues', [
            'title' => 'Tokyo Words',
            'type' => 7,
            'tags' => '#tokyo #study #tokyo',
        ]);

        $response->assertStatus(201);
        $this->assertTrue(Str::isUuid((string) $response->json('uuid')));

        $catalogue = PersistenceCatalogue::where('uuid', $response->json('uuid'))->first();

        $this->assertNotNull($catalogue);
        $this->assertSame($user->id, $catalogue->user_id);
        $this->assertSame('', $catalogue->description);
        $this->assertFalse($catalogue->publicity);
        $this->assertSame(ObjectTemplateType::LIST->value, $catalogue->entity_type_uuid);

        $hashtags = $this->getHashtagContents($catalogue);
        sort($hashtags);
        $this->assertSame(['#study', '#tokyo'], $hashtags);

        $this->assertSame(1, View::where('template_id', ObjectTemplateType::LIST->getLegacyId())
            ->where('real_object_id', $catalogue->id)
            ->where('user_id', $user->id)
            ->count());
    }

    public function test_store_returns_validation_error_for_invalid_payload(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('POST', '/api/v1/catalogues', [
            'type' => 4,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type']);
    }

    public function test_update_allows_owner_to_partially_update_and_replace_hashtags(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user, [
            'title' => 'Before Update',
            'description' => 'Keep this description',
            'publicity' => false,
            'type' => 5,
        ]);
        $this->attachHashtags($catalogue, ['#old']);
        DB::table('customlist_object')->insert([
            [
                'list_id' => $catalogue->id,
                'listtype_id' => 8,
                'real_object_id' => 101,
            ],
            [
                'list_id' => $catalogue->id,
                'listtype_id' => 8,
                'real_object_id' => 102,
            ],
        ]);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'title' => 'After Update',
            'type' => 8,
            'publicity' => true,
            'tags' => '#new1 #new2',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('title', 'After Update')
            ->assertJsonPath('type', 8)
            ->assertJsonPath('publicity', 1)
            ->assertJsonPath('description', 'Keep this description')
            ->assertJsonPath('items_count', 2);

        $catalogue->refresh();

        $this->assertSame('After Update', $catalogue->title);
        $this->assertSame('Keep this description', $catalogue->description);
        $this->assertSame(8, $catalogue->type->value);
        $this->assertTrue($catalogue->publicity);

        $hashtags = $this->getHashtagContents($catalogue);
        sort($hashtags);
        $this->assertSame(['#new1', '#new2'], $hashtags);

        $responseHashtags = collect($response->json('hashtags'))
            ->pluck('content')
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['#new1', '#new2'], $responseHashtags);
    }

    public function test_update_allows_owner_to_make_private_catalogue_public_with_publicity_only_payload(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'publicity' => false,
        ]);

        Passport::actingAs($owner, ['*'], 'api');

        $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'publicity' => true,
        ])->assertStatus(200)
            ->assertJsonPath('publicity', 1)
            ->assertJsonPath('title', 'Original Catalogue');

        $catalogue->refresh();

        $this->assertTrue($catalogue->publicity);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(200)
            ->assertJsonPath('uuid', $catalogue->uuid);

        $this->json('GET', '/api/v1/catalogues')
            ->assertStatus(200)
            ->assertJsonFragment(['uuid' => $catalogue->uuid]);
    }

    public function test_update_allows_owner_to_make_public_catalogue_private_with_publicity_only_payload(): void
    {
        $owner = $this->createUser();
        $viewer = $this->createUser();
        $catalogue = $this->createCatalogue($owner, [
            'publicity' => true,
        ]);

        Passport::actingAs($owner, ['*'], 'api');

        $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'publicity' => false,
        ])->assertStatus(200)
            ->assertJsonPath('publicity', 0)
            ->assertJsonPath('title', 'Original Catalogue');

        $catalogue->refresh();

        $this->assertFalse($catalogue->publicity);

        Passport::actingAs($viewer, ['*'], 'api');

        $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(403);

        $this->json('GET', '/api/v1/catalogues')
            ->assertStatus(200)
            ->assertJsonMissing(['uuid' => $catalogue->uuid]);
    }

    public function test_update_empty_payload_returns_validation_problem(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", []);

        $response->assertStatus(422)
            ->assertJsonPath('title', 'No fields to update')
            ->assertJsonPath('errors.fields.0', 'At least one field must be provided for update operation');
    }

    public function test_update_forbids_non_owner(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $catalogue = $this->createCatalogue($owner);

        Passport::actingAs($otherUser, ['*'], 'api');

        $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'title' => 'Unauthorized Update',
        ])->assertStatus(403);
    }

    public function test_update_forbids_admin_when_admin_is_not_owner_for_publicity_only_update(): void
    {
        $owner = $this->createUser();
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        $catalogue = $this->createCatalogue($owner, [
            'publicity' => true,
        ]);

        Passport::actingAs($admin, ['*'], 'api');

        $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'publicity' => false,
        ])->assertStatus(403);

        $catalogue->refresh();

        $this->assertTrue($catalogue->publicity);
    }

    public function test_update_returns_not_found_for_unknown_uuid(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', '/api/v1/catalogues/'.(string) Str::uuid(), [
            'title' => 'Missing Catalogue',
        ])->assertStatus(404);
    }

    public function test_update_tags_empty_string_clears_existing_hashtags(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);
        $this->attachHashtags($catalogue, ['#one', '#two']);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'tags' => '',
        ])->assertStatus(200);

        $this->assertSame([], $this->getHashtagContents($catalogue));
    }

    public function test_destroy_allows_owner_to_delete_catalogue_and_related_records(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);
        $this->attachHashtags($catalogue, ['#tokyo']);

        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => 123,
        ]);

        View::create([
            'user_id' => $user->id,
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'user_ip' => '127.0.0.1',
        ]);

        Download::create([
            'user_id' => $user->id,
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
        ]);

        Like::create([
            'user_id' => $user->id,
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'value' => 1,
        ]);

        $comment = Comment::create([
            'uuid' => (string) Str::uuid(),
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $catalogue->id,
            'user_id' => $user->id,
            'content' => 'Delete me',
        ]);

        Like::create([
            'user_id' => $user->id,
            'template_id' => ObjectTemplateType::COMMENT->getLegacyId(),
            'real_object_id' => $comment->id,
            'value' => 1,
        ]);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertNoContent();

        $this->assertNull(PersistenceCatalogue::find($catalogue->id));
        $this->assertSame(0, DB::table('customlist_object')->where('list_id', $catalogue->id)->count());
        $this->assertSame(0, View::where('real_object_id', $catalogue->id)->count());
        $this->assertSame(0, Download::where('real_object_id', $catalogue->id)->count());
        $this->assertSame(0, Like::where('template_id', ObjectTemplateType::LIST->getLegacyId())
            ->where('real_object_id', $catalogue->id)
            ->count());
        $this->assertSame(0, Comment::where('real_object_id', $catalogue->id)->count());
        $this->assertSame(0, Like::where('template_id', ObjectTemplateType::COMMENT->getLegacyId())
            ->where('real_object_id', $comment->id)
            ->count());
        $this->assertSame(0, HashtagEntity::where('entity_id', $catalogue->id)->count());
    }

    public function test_destroy_forbids_non_owner(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $catalogue = $this->createCatalogue($owner);

        Passport::actingAs($otherUser, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(403);
    }

    public function test_destroy_returns_not_found_for_unknown_uuid(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', '/api/v1/catalogues/'.(string) Str::uuid())
            ->assertStatus(404);
    }
}
