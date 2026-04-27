<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
