<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Users\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $created = [];
        $existing = [];

        foreach (PermissionCatalog::allPermissionNames() as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => PermissionCatalog::guardName(),
            ]);

            if ($permission->wasRecentlyCreated) {
                $created[] = $permissionName;
            } else {
                $existing[] = $permissionName;
            }
        }

        $this->command?->info('Permissions seed complete.');

        if ($created !== []) {
            $this->command?->info('Created permissions: '.implode(', ', $created));
        }

        if ($existing !== []) {
            $this->command?->info('Existing permissions: '.implode(', ', $existing));
        }

        // Reported, never deleted: a role may still reference a renamed or retired permission, and
        // removing it should be a deliberate migration.
        $orphaned = Permission::query()
            ->where('guard_name', PermissionCatalog::guardName())
            ->whereNotIn('name', PermissionCatalog::allPermissionNames())
            ->orderBy('name')
            ->pluck('name')
            ->all();

        if ($orphaned !== []) {
            $this->command?->warn('Permissions not in the catalogue: '.implode(', ', $orphaned));
        }
    }
}
