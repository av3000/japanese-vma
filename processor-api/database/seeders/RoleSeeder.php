<?php

namespace Database\Seeders;

use App\Domain\Shared\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles from your UserRole enum
        foreach (UserRole::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => 'api'
            ]);
        }

        Role::firstOrCreate(['name' => 'testuser', 'guard_name' => 'api']);
        // TODO: Create permissions and assign to roles
        // $editArticles = Permission::create(['name' => 'edit articles', 'guard_name' => 'api']);
        // $adminRole->givePermissionTo($editArticles);

        $this->command->info('✅ Roles created: admin, user');
    }
}
