<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue as PersistenceCatalogue;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogueItemMutationTest extends TestCase
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
        $fillableAttributes = array_intersect_key($overrides, array_flip([
            'user_id',
            'uuid',
            'entity_type_uuid',
            'title',
            'description',
            'publicity',
            'type',
        ]));
        $catalogue = PersistenceCatalogue::create(array_merge([
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'title' => 'Mutation Catalogue',
            'description' => 'Mutation description',
            'publicity' => false,
            'type' => SavedListType::WORDS->value,
        ], $fillableAttributes));

        $nonFillableAttributes = array_diff_key($overrides, $fillableAttributes);
        if ($nonFillableAttributes !== []) {
            DB::table('customlists')
                ->where('id', $catalogue->id)
                ->update($nonFillableAttributes);

            $catalogue->refresh();
        }

        return $catalogue;
    }

    private function attachCatalogueItem(PersistenceCatalogue $catalogue, int $itemId): void
    {
        DB::table('customlist_object')->insert([
            'list_id' => $catalogue->id,
            'listtype_id' => $catalogue->type->value,
            'real_object_id' => $itemId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKanji(int $id, string $jlpt): void
    {
        DB::table('japanese_kanji_bank_long')->insert([
            'id' => $id,
            'uuid' => (string) Str::uuid(),
            'kanji' => 'X',
            'onyomi' => 'gaku',
            'kunyomi' => 'manabu',
            'meaning' => 'study',
            'nanori' => '',
            'grade' => '1',
            'stroke_count' => '8',
            'jlpt' => $jlpt,
            'frequency' => '1',
            'radicals' => 'child',
            'radical_parts' => 'child',
        ]);
    }

    public function test_owner_can_remove_existing_item_from_catalogue(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);
        $this->attachCatalogueItem($catalogue, 321);

        Passport::actingAs($user, ['*'], 'api');

        $response = $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}/items/321");

        $response->assertNoContent();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('customlist_object', [
            'list_id' => $catalogue->id,
            'real_object_id' => 321,
        ]);
    }

    public function test_remove_deletes_customlist_object_for_correct_list_and_item(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);
        $otherCatalogue = $this->createCatalogue($user);

        $this->attachCatalogueItem($catalogue, 321);
        $this->attachCatalogueItem($otherCatalogue, 321);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}/items/321")
            ->assertNoContent();

        $this->assertDatabaseMissing('customlist_object', [
            'list_id' => $catalogue->id,
            'real_object_id' => 321,
        ]);
        $this->assertDatabaseHas('customlist_object', [
            'list_id' => $otherCatalogue->id,
            'real_object_id' => 321,
        ]);
    }

    public function test_remove_forbids_non_owner(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();
        $catalogue = $this->createCatalogue($owner);
        $this->attachCatalogueItem($catalogue, 321);

        Passport::actingAs($otherUser, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}/items/321")
            ->assertStatus(403);
    }

    public function test_remove_returns_not_found_for_unknown_catalogue_uuid(): void
    {
        $user = $this->createUser();

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', '/api/v1/catalogues/' . (string) Str::uuid() . '/items/321')
            ->assertStatus(404);
    }

    public function test_remove_returns_not_found_when_item_is_not_in_catalogue(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}/items/321")
            ->assertStatus(404);
    }

    public function test_remove_decrements_legacy_jlpt_counter_for_kanji_catalogue(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user, [
            'type' => SavedListType::KANJIS->value,
            'n3' => 1,
        ]);
        $this->createKanji(321, '3');
        $this->attachCatalogueItem($catalogue, 321);

        Passport::actingAs($user, ['*'], 'api');

        $this->json('DELETE', "/api/v1/catalogues/{$catalogue->uuid}/items/321")
            ->assertNoContent();

        $catalogue->refresh();

        $this->assertSame(0, $catalogue->getAttribute('n3'));
    }
}
