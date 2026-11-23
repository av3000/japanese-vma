<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Concerns;

use App\Domain\Shared\Enums\UserRole;
use App\Infrastructure\Persistence\Models\User;
use Database\Seeders\ObjectTemplatesTableSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Panel test setup: seeded catalogue, system roles and object templates, plus users built from
 * roles or from a bare permission list.
 */
trait InteractsWithAdminPanel
{
    protected function seedAdminPanel(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class, ObjectTemplatesTableSeeder::class]);
    }

    protected function createAdmin(string $name = 'Panel Admin'): User
    {
        return $this->createPanelUser($name, roles: [UserRole::ADMIN->value]);
    }

    /**
     * A non-admin user whose only panel rights are the given permissions, through a dedicated role.
     *
     * @param list<string> $permissions
     */
    protected function createUserWithPermissions(array $permissions, string $name = 'Limited User'): User
    {
        $role = Role::create(['name' => 'role-'.Str::lower(Str::random(8)), 'guard_name' => 'api']);
        $role->givePermissionTo($permissions);

        return $this->createPanelUser($name, roles: [$role->name]);
    }

    /**
     * @param list<string> $roles
     */
    protected function createPanelUser(string $name = 'Member', array $roles = []): User
    {
        $user = User::create([
            'name' => $name,
            'email' => Str::uuid().'@example.com',
            'password' => Hash::make('password'),
            'uuid' => (string) Str::uuid(),
        ]);

        if ($roles !== []) {
            $user->syncRoles([UserRole::COMMON->value, ...$roles]);
        }

        return $user->refresh();
    }

    protected function actingAsPanelUser(User $user): static
    {
        $this->actingAs($user, 'web');
        Filament::setCurrentPanel('admin');

        return $this;
    }
}
