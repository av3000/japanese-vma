<?php

namespace Tests\Feature\Catalogues;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue as PersistenceCatalogue;
use App\Infrastructure\Persistence\Models\HashtagEntity;
use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;

class StoreUpdateCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);

        DB::table('objecttemplates')->insert([
            'id' => ObjectTemplateType::LIST->getLegacyId(),
            'title' => 'list',
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => Str::uuid() . '@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ], $overrides));
    }

    private function createCatalogue(User $user, array $overrides = []): PersistenceCatalogue
    {
        return PersistenceCatalogue::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
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
        $this->assertTrue(Str::isUuid((string) $response->json('data.uuid')));

        $catalogue = PersistenceCatalogue::where('uuid', $response->json('data.uuid'))->first();

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

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
            'title' => 'After Update',
            'type' => 8,
            'publicity' => true,
            'tags' => '#new1 #new2',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'After Update')
            ->assertJsonPath('data.type', 8)
            ->assertJsonPath('data.publicity', 1)
            ->assertJsonPath('data.description', 'Keep this description');

        $catalogue->refresh();

        $this->assertSame('After Update', $catalogue->title);
        $this->assertSame('Keep this description', $catalogue->description);
        $this->assertSame(8, $catalogue->type->value);
        $this->assertTrue($catalogue->publicity);

        $hashtags = $this->getHashtagContents($catalogue);
        sort($hashtags);
        $this->assertSame(['#new1', '#new2'], $hashtags);
    }

    public function test_update_empty_payload_returns_validation_problem(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", []);

        $response->assertStatus(422)
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

    public function test_update_returns_not_found_for_unknown_uuid(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['*'], 'api');

        $this->json('PUT', '/api/v1/catalogues/' . (string) Str::uuid(), [
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
}
