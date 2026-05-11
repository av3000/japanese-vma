<?php

namespace Tests\Feature\Catalogues;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\View;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;

class IndexCataloguesTest extends TestCase
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

    public function test_index_returns_only_public_custom_lists(): void
    {
        $user = $this->createUser();

        $publicCustom = $this->createCatalogue($user, ['title' => 'Public Custom', 'publicity' => 1, 'type' => 5]);
        $this->createCatalogue($user, ['title' => 'Private Custom', 'publicity' => 0, 'type' => 5]);
        $this->createCatalogue($user, ['title' => 'Public Known', 'publicity' => 1, 'type' => 1]);

        $response = $this->json('GET', '/api/v1/catalogues');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $publicCustom->uuid);
    }

    public function test_index_sorts_by_views(): void
    {
        $user = $this->createUser();

        $lowViews = $this->createCatalogue($user, ['title' => 'Low Views', 'publicity' => 1, 'type' => 5]);
        $highViews = $this->createCatalogue($user, ['title' => 'High Views', 'publicity' => 1, 'type' => 5]);

        View::create([
            'user_id' => null,
            'user_ip' => '127.0.0.1',
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $lowViews->id,
        ]);

        View::create([
            'user_id' => null,
            'user_ip' => '127.0.0.2',
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $highViews->id,
        ]);
        View::create([
            'user_id' => null,
            'user_ip' => '127.0.0.3',
            'template_id' => ObjectTemplateType::LIST->getLegacyId(),
            'real_object_id' => $highViews->id,
        ]);

        $response = $this->json('GET', '/api/v1/catalogues?sort_by=views&sort_dir=desc');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.uuid', $highViews->uuid);
    }

    public function test_index_filters_by_search(): void
    {
        $user = $this->createUser();

        $match = $this->createCatalogue($user, ['title' => 'Tokyo Words', 'publicity' => 1, 'type' => 5]);
        $this->createCatalogue($user, ['title' => 'Osaka Kanjis', 'publicity' => 1, 'type' => 5]);

        $response = $this->json('GET', '/api/v1/catalogues?search=Tokyo');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $match->uuid);
    }

    public function test_index_returns_owned_private_and_known_catalogues_when_owner_scope_is_requested(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();

        $knownOwned = $this->createCatalogue($owner, ['title' => 'Known Owned', 'publicity' => 0, 'type' => 1]);
        $customOwned = $this->createCatalogue($owner, ['title' => 'Custom Owned', 'publicity' => 0, 'type' => 5]);
        $this->createCatalogue($otherUser, ['title' => 'Other Private', 'publicity' => 0, 'type' => 5]);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues', [
            'owner_uid' => $owner->uuid,
            'public_only' => false,
            'custom_only' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonFragment(['uuid' => $knownOwned->uuid])
            ->assertJsonFragment(['uuid' => $customOwned->uuid]);
    }

    public function test_index_non_admin_cannot_read_other_private_catalogues_with_owner_scope(): void
    {
        $viewer = $this->createUser();
        $owner = $this->createUser();

        $publicCatalogue = $this->createCatalogue($owner, ['title' => 'Owner Public', 'publicity' => 1, 'type' => 5]);
        $this->createCatalogue($owner, ['title' => 'Owner Private', 'publicity' => 0, 'type' => 5]);

        Passport::actingAs($viewer, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues', [
            'owner_uid' => $owner->uuid,
            'public_only' => false,
            'custom_only' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $publicCatalogue->uuid);
    }

    public function test_index_admin_can_read_other_private_catalogues_with_owner_scope(): void
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        $owner = $this->createUser();

        $privateCatalogue = $this->createCatalogue($owner, ['title' => 'Owner Private', 'publicity' => 0, 'type' => 5]);

        Passport::actingAs($admin, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues', [
            'owner_uid' => $owner->uuid,
            'public_only' => false,
            'custom_only' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $privateCatalogue->uuid);
    }

    public function test_index_filters_owned_catalogues_by_type(): void
    {
        $owner = $this->createUser();

        $articleList = $this->createCatalogue($owner, ['title' => 'Article List', 'publicity' => 0, 'type' => 9]);
        $this->createCatalogue($owner, ['title' => 'Kanji List', 'publicity' => 0, 'type' => 6]);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues', [
            'owner_uid' => $owner->uuid,
            'type' => 9,
            'public_only' => false,
            'custom_only' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.uuid', $articleList->uuid);
    }

    public function test_index_rejects_invalid_owner_uid(): void
    {
        $response = $this->json('GET', '/api/v1/catalogues', [
            'owner_uid' => 'not-a-uuid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['owner_uid']);
    }
}
