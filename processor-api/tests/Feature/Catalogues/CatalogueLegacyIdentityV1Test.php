<?php

namespace Tests\Feature\Catalogues;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
use Tests\Support\SeedsBaselineData;
use Tests\TestCase;

class CatalogueLegacyIdentityV1Test extends TestCase
{
    use RefreshDatabase;
    use SeedsBaselineData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedBaselineData();
    }

    public function test_resolves_public_catalogue_for_anonymous_viewer(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 1]);

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")
            ->assertOk()
            ->assertExactJson([
                'id' => $catalogue->id,
                'uuid' => $catalogue->uuid,
            ]);
    }

    /**
     * The whole point of the resolver is that it does not pay for the detail
     * payload. Assert the absent keys explicitly so a future "just add the
     * title" change has to argue with a test.
     */
    public function test_response_omits_detail_assembly(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 1]);

        $response = $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")->assertOk();

        foreach (['title', 'description', 'type', 'type_label', 'items', 'items_count', 'hashtags', 'engagement', 'owner', 'created_at'] as $absentKey) {
            $response->assertJsonMissingPath($absentKey);
        }
    }

    public function test_resolution_does_not_increment_views(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 1]);

        $this->assertSame(0, $this->viewCountFor($catalogue));

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")->assertOk();

        $this->assertSame(0, $this->viewCountFor($catalogue));
    }

    public function test_resolves_private_catalogue_for_owner(): void
    {
        $owner = $this->createUser();
        $catalogue = $this->createCatalogue($owner, ['publicity' => 0]);

        Passport::actingAs($owner, ['*'], 'api');

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")
            ->assertOk()
            ->assertJsonPath('uuid', $catalogue->uuid);
    }

    public function test_resolves_private_catalogue_for_admin(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 0]);

        $this->actingAsAdmin();

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")
            ->assertOk()
            ->assertJsonPath('uuid', $catalogue->uuid);
    }

    /**
     * 404 rather than the 403 `catalogues/{uuid}` returns: a 403 here would
     * confirm that legacy list N exists and is private.
     */
    public function test_private_catalogue_is_not_disclosed_to_anonymous_viewer(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 0]);

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")
            ->assertStatus(404);
    }

    public function test_private_catalogue_is_not_disclosed_to_other_authenticated_user(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 0]);

        Passport::actingAs($this->createUser(), ['*'], 'api');

        $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")
            ->assertStatus(404);
    }

    /**
     * A missing catalogue and a hidden one must be indistinguishable, body included.
     */
    public function test_missing_and_hidden_catalogues_are_indistinguishable(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 0]);
        $missingId = $catalogue->id + 1;

        $hidden = $this->getJson("/api/v1/catalogues/legacy/{$catalogue->id}")->assertStatus(404)->json();
        $missing = $this->getJson("/api/v1/catalogues/legacy/{$missingId}")->assertStatus(404)->json();

        $this->assertSame(array_keys($missing), array_keys($hidden));
        $this->assertSame($missing['type'], $hidden['type']);
        $this->assertSame($missing['title'], $hidden['title']);
        $this->assertSame($missing['status'], $hidden['status']);

        // The messages may differ only where the caller's own id is echoed back.
        $this->assertSame(
            str_replace((string) $missingId, '{id}', $missing['detail']),
            str_replace((string) $catalogue->id, '{id}', $hidden['detail']),
        );
    }

    public function test_unknown_id_returns_not_found(): void
    {
        $this->getJson('/api/v1/catalogues/legacy/999999')
            ->assertStatus(404);
    }

    /**
     * The route constraint, not a FormRequest, is what rejects these. A 422
     * would need a query parameter in the OpenAPI document and would also say
     * more about an id than the 404 for a private catalogue does, so every
     * unusable identifier collapses to the same 404.
     */
    public function test_rejects_identifiers_that_are_not_catalogue_ids(): void
    {
        $unusable = [
            '0',                     // not a valid primary key
            '00',                    // leading zeros
            '007',                   // leading zeros
            '99999999999999999999',  // would clamp to PHP_INT_MAX when cast
            'abc',
            '-1',
            '1.5',
            '1e3',
            '',
        ];

        foreach ($unusable as $identifier) {
            $this->getJson("/api/v1/catalogues/legacy/{$identifier}")
                ->assertStatus(404);
        }
    }

    public function test_accepts_the_largest_in_range_identifier(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/api/v1/catalogues/legacy/999999999999999999', 'GET'),
        );

        $this->assertSame(
            'App\\Http\\v1\\Catalogues\\Controllers\\CatalogueController@resolveLegacyId',
            $route->getActionName(),
        );
    }

    /**
     * `catalogues/{uuid}` has no uuid constraint, so without one the bare
     * `catalogues/legacy` path resolves to CatalogueController@show with a uuid
     * of "legacy". EntityId::from() then throws InvalidArgumentException, which
     * app/Exceptions/Handler.php does not map - a 500 instead of a 404.
     */
    public function test_legacy_segment_is_not_consumed_as_a_catalogue_uuid(): void
    {
        $this->getJson('/api/v1/catalogues/legacy')
            ->assertStatus(404);
    }

    public function test_legacy_route_resolves_the_resolver_action(): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create('/api/v1/catalogues/legacy/42', 'GET'),
        );

        $this->assertSame(
            'App\\Http\\v1\\Catalogues\\Controllers\\CatalogueController@resolveLegacyId',
            $route->getActionName(),
        );
    }

    public function test_detail_route_still_resolves_for_a_uuid(): void
    {
        $catalogue = $this->createCatalogue($this->createUser(), ['publicity' => 1]);

        $this->getJson("/api/v1/catalogues/{$catalogue->uuid}")
            ->assertOk()
            ->assertJsonPath('uuid', $catalogue->uuid);
    }

    private function viewCountFor(Catalogue $catalogue): int
    {
        return View::where('real_object_id', $catalogue->id)
            ->where('template_id', ObjectTemplateType::LIST->getLegacyId())
            ->count();
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function actingAsAdmin(): User
    {
        $admin = $this->createUser();
        $admin->assignRole(UserRole::ADMIN->value);
        Passport::actingAs($admin, ['*'], 'api');

        return $admin;
    }

    private function createCatalogue(User $user, array $overrides = []): Catalogue
    {
        return Catalogue::factory()
            ->byUser($user)
            ->create(array_merge([
                'title' => 'Test Catalogue',
                'description' => 'Test description',
                'publicity' => 1,
                'type' => SavedListType::RADICALS,
            ], $overrides));
    }
}
