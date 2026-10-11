<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\InteractsWithAdminPanel;
use Tests\TestCase;

/**
 * Issue #192: Horizon accepts the admin panel session for users with operations.view, keeps HTTP
 * basic auth for non-browser access, and still challenges anonymous requests. The test
 * environment is `testing`, so the middleware's `local` bypass does not apply.
 */
class HorizonAccessTest extends TestCase
{
    use InteractsWithAdminPanel;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAdminPanel();
        config(['horizon.basic_auth' => ['username' => 'ops', 'password' => 'secret']]);
    }

    public function test_panel_session_with_operations_view_opens_horizon_without_basic_auth(): void
    {
        $this->actingAs($this->createUserWithPermissions(['operations.view']), 'web')
            ->get('/horizon')
            ->assertOk();
    }

    public function test_admin_session_opens_horizon(): void
    {
        $this->actingAs($this->createAdmin(), 'web')
            ->get('/horizon')
            ->assertOk();
    }

    public function test_panel_session_without_the_permission_is_forbidden(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/horizon')
            ->assertForbidden();
    }

    public function test_anonymous_request_without_credentials_is_challenged(): void
    {
        $this->get('/horizon')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Basic realm="Horizon"');
    }

    public function test_basic_auth_still_works_for_non_browser_access(): void
    {
        $this->withHeaders(['Authorization' => 'Basic '.base64_encode('ops:secret')])
            ->get('/horizon')
            ->assertOk();
    }

    public function test_panel_navigation_links_to_horizon_for_operators(): void
    {
        $this->actingAs($this->createUserWithPermissions(['operations.view']), 'web')
            ->get('/admin')
            ->assertOk()
            ->assertSee(url('horizon'), false);
    }

    public function test_panel_navigation_hides_horizon_from_others(): void
    {
        $this->actingAs($this->createUserWithPermissions(['articles.view']), 'web')
            ->get('/admin')
            ->assertOk()
            ->assertDontSee(url('horizon'), false);
    }
}
