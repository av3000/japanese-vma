<?php

namespace Tests\Feature\Catalogues;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\View;
use App\Domain\Shared\Enums\ObjectTemplateType;

class IndexCataloguesTest extends TestCase
{
    use RefreshDatabase;

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
}
