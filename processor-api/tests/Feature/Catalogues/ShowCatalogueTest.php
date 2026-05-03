<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShowCatalogueTest extends TestCase
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
            'publicity' => 1,
            'type' => 5,
        ], $overrides));
    }

    public function test_show_public_catalogue_returns_ok(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user, ['publicity' => 1, 'type' => 5]);

        $response = $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.uuid', $catalogue->uuid)
            ->assertJsonMissingPath('data.catalogue');
    }

    public function test_show_private_catalogue_requires_owner(): void
    {
        $owner = $this->createUser();
        $catalogue = $this->createCatalogue($owner, ['publicity' => 0, 'type' => 5]);

        $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(403);

        Passport::actingAs($owner, ['*'], 'api');

        $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(200);
    }

    public function test_show_increments_view(): void
    {
        $user = $this->createUser();
        $catalogue = $this->createCatalogue($user, ['publicity' => 1, 'type' => 5]);

        $this->assertSame(0, View::where('real_object_id', $catalogue->id)->count());

        $this->json('GET', "/api/v1/catalogues/{$catalogue->uuid}")
            ->assertStatus(200);

        $this->assertSame(1, View::where('real_object_id', $catalogue->id)
            ->where('template_id', ObjectTemplateType::LIST->getLegacyId())
            ->count());
    }
}
