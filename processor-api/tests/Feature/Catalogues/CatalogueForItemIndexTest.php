<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogueForItemIndexTest extends TestCase
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
            'publicity' => 0,
            'type' => 5,
        ], $overrides));
    }

    private function attachCatalogueItem(Catalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => $itemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_for_item_index_returns_owned_catalogues_with_contains_item_flag(): void
    {
        $owner = $this->createUser();
        $matchingCatalogue = $this->createCatalogue($owner, [
            'title' => 'Known Words',
            'type' => 3,
            'publicity' => 0,
        ]);
        $otherOwnedCatalogue = $this->createCatalogue($owner, [
            'title' => 'Words To Review',
            'type' => 7,
            'publicity' => 1,
        ]);
        $otherUser = $this->createUser();
        $this->createCatalogue($otherUser, [
            'title' => 'Other User Catalogue',
            'type' => 7,
        ]);

        $this->attachCatalogueItem($matchingCatalogue, 321);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues/for-item?item_id=321');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.contains_item', true)
            ->assertJsonPath('items.0.publicity', 0)
            ->assertJsonPath('items.1.contains_item', false)
            ->assertJsonMissing(['title' => 'Other User Catalogue']);
    }

    public function test_for_item_index_filters_owned_catalogues_by_requested_types(): void
    {
        $owner = $this->createUser();
        $knownWords = $this->createCatalogue($owner, ['title' => 'Known Words', 'type' => 3]);
        $words = $this->createCatalogue($owner, ['title' => 'Words', 'type' => 7]);
        $this->createCatalogue($owner, ['title' => 'Articles', 'type' => 9]);

        $this->attachCatalogueItem($knownWords, 321);
        $this->attachCatalogueItem($words, 321);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues/for-item', [
            'item_id' => 321,
            'types' => [3, 7],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'items')
            ->assertJsonFragment(['uuid' => $knownWords->uuid])
            ->assertJsonFragment(['uuid' => $words->uuid])
            ->assertJsonMissing(['title' => 'Articles']);
    }

    public function test_for_item_index_filters_owned_catalogues_by_search(): void
    {
        $owner = $this->createUser();
        $match = $this->createCatalogue($owner, ['title' => 'Tokyo Words', 'type' => 7]);
        $this->createCatalogue($owner, ['title' => 'Osaka Kanjis', 'type' => 6]);

        Passport::actingAs($owner, ['*'], 'api');

        $response = $this->json('GET', '/api/v1/catalogues/for-item', [
            'item_id' => 321,
            'search' => 'Tokyo',
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.uuid', $match->uuid);
    }

    public function test_for_item_index_requires_authentication(): void
    {
        $this->json('GET', '/api/v1/catalogues/for-item?item_id=321')
            ->assertStatus(401);
    }

    public function test_for_item_index_validates_item_id_and_types(): void
    {
        $owner = $this->createUser();

        Passport::actingAs($owner, ['*'], 'api');

        $this->json('GET', '/api/v1/catalogues/for-item', [
            'item_id' => 'nope',
            'types' => ['bad'],
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['item_id', 'types.0']);
    }
}
