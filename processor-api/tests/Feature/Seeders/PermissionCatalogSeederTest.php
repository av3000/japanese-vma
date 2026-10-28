<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Application\Users\Support\PermissionCatalog;
use App\Domain\Shared\Enums\UserRole;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_catalog_seeds_expected_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        self::assertSame(
            PermissionCatalog::allPermissionNames(),
            Permission::query()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_permission_catalog_groups_are_defined_explicitly_in_code(): void
    {
        $groups = PermissionCatalog::groups();

        self::assertSame(['admin', 'content', 'community', 'study', 'operations'], array_keys($groups));
        self::assertSame(
            [
                'roles.view', 'roles.create', 'roles.update', 'roles.delete',
                'users.view', 'users.update', 'roles.assign', 'activity.view',
            ],
            array_keys($groups['admin']['permissions']),
        );
        self::assertSame(
            [
                'articles.view', 'articles.update', 'articles.delete',
                'catalogues.view', 'catalogues.update', 'catalogues.delete',
                'comments.view', 'comments.delete',
            ],
            array_keys($groups['content']['permissions']),
        );
        self::assertSame(['posts.view', 'posts.moderate', 'hashtags.manage'], array_keys($groups['community']['permissions']));
        self::assertSame(['study.view', 'sentences.moderate'], array_keys($groups['study']['permissions']));
        self::assertSame(['operations.view', 'operations.manage'], array_keys($groups['operations']['permissions']));
    }

    public function test_seeding_twice_is_idempotent_and_admin_holds_every_catalogue_permission(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        self::assertSame(count(PermissionCatalog::allPermissionNames()), Permission::query()->count());
        self::assertSame(2, Role::query()->count());

        $admin = Role::findByName(UserRole::ADMIN->value, 'api');
        self::assertSame(
            PermissionCatalog::allPermissionNames(),
            $admin->permissions()->pluck('name')->sort()->values()->all(),
        );
    }

    public function test_common_role_holds_no_permission_after_seeding(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        self::assertSame([], Role::findByName(UserRole::COMMON->value, 'api')->permissions()->pluck('name')->all());
    }

    public function test_the_pruning_migration_removes_the_unchecked_permissions_and_their_grants(): void
    {
        $role = Role::create(['name' => 'legacy-editor', 'guard_name' => 'api']);

        foreach (['articles.create', 'catalogues.create', 'comments.update'] as $name) {
            $role->givePermissionTo(Permission::create(['name' => $name, 'guard_name' => 'api']));
        }

        $role->givePermissionTo(Permission::create(['name' => 'articles.view', 'guard_name' => 'api']));

        $migration = require database_path('migrations/2026_10_27_000000_remove_unchecked_panel_permissions.php');
        $migration->up();

        self::assertSame(['articles.view'], Permission::query()->pluck('name')->all());
        self::assertSame(['articles.view'], $role->fresh()->permissions()->pluck('name')->all());
    }

    public function test_permissions_missing_from_the_catalogue_are_kept(): void
    {
        Permission::create(['name' => 'retired.permission', 'guard_name' => 'api']);

        $this->seed(PermissionSeeder::class);

        self::assertTrue(Permission::query()->where('name', 'retired.permission')->exists());
    }
}
