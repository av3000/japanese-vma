<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Application\Users\Support\PanelPermission;
use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        Role::firstOrCreate(['name' => UserRole::COMMON->value, 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => UserRole::ADMIN->value, 'guard_name' => 'api']);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        $user = $this->createUser();

        $this->actingAs($user, 'web')
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = $this->createUser([UserRole::ADMIN->value]);

        $this->actingAs($admin, 'web')
            ->get('/admin')
            ->assertOk();
    }

    public function test_admin_without_explicit_permissions_passes_every_catalogue_check(): void
    {
        $admin = $this->createUser([UserRole::ADMIN->value]);

        self::assertSame([], $admin->getAllPermissions()->all());

        foreach (PanelPermission::cases() as $permission) {
            self::assertTrue($admin->hasPanelPermission($permission), $permission->value);
        }

        $this->actingAs($admin, 'web')->get('/admin/users')->assertOk();
        $this->actingAs($admin, 'web')->get('/admin/roles')->assertOk();
    }

    public function test_user_with_only_users_view_reaches_users_but_not_roles(): void
    {
        $role = Role::create(['name' => 'support', 'guard_name' => 'api']);
        $role->givePermissionTo('users.view');
        $support = $this->createUser(['support']);

        $this->actingAs($support, 'web')->get('/admin')->assertOk();
        $this->actingAs($support, 'web')->get('/admin/users')->assertOk();
        $this->actingAs($support, 'web')->get('/admin/roles')->assertForbidden();
        $this->actingAs($support, 'web')->get('/admin/users/create')->assertForbidden();
    }

    public function test_permission_outside_the_catalogue_does_not_open_the_panel(): void
    {
        Permission::create(['name' => 'legacy.unlisted', 'guard_name' => 'api']);
        $role = Role::create(['name' => 'legacy', 'guard_name' => 'api']);
        $role->givePermissionTo('legacy.unlisted');
        $user = $this->createUser(['legacy']);

        $this->actingAs($user, 'web')->get('/admin')->assertForbidden();
    }

    /**
     * @param array<int, string> $roles
     */
    private function createUser(array $roles = []): User
    {
        $user = User::create([
            'name' => 'Filament Admin Test User',
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);

        if ($roles !== []) {
            $user->syncRoles($roles);
        }

        return $user;
    }
}
