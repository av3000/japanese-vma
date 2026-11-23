<?php

namespace Database\Seeders;

use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Shared\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = [];
        $existing = [];

        // Create roles from your UserRole enum (idempotent via firstOrCreate).
        foreach (UserRole::cases() as $role) {
            $spatieRole = Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => PermissionCatalog::guardName(),
            ]);
            if ($spatieRole->wasRecentlyCreated) {
                $created[] = $spatieRole->name;
            } else {
                $existing[] = $spatieRole->name;
            }
        }

        // The admin role holds every seeded catalogue permission, so the Role resource shows what
        // the superuser check already allows. Run PermissionSeeder first; missing rows are skipped.
        $adminRole = Role::findByName(UserRole::ADMIN->value, PermissionCatalog::guardName());
        $catalogPermissions = Permission::query()
            ->where('guard_name', PermissionCatalog::guardName())
            ->whereIn('name', PermissionCatalog::allPermissionNames())
            ->get();
        $adminRole->givePermissionTo($catalogPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('✅ Roles seed complete.');
        if ($created !== []) {
            $this->command?->info('Created: '.implode(', ', $created));
        }
        if ($existing !== []) {
            $this->command?->info('Already existed: '.implode(', ', $existing));
        }
    }
}
