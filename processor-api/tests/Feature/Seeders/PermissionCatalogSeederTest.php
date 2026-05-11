<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders;

use App\Application\Users\Support\PermissionCatalog;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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

        self::assertArrayHasKey('admin', $groups);
        self::assertArrayHasKey('content', $groups);
        self::assertSame(
            ['roles.view', 'roles.create', 'roles.update', 'roles.delete', 'users.view', 'users.update'],
            array_keys($groups['admin']['permissions']),
        );
    }
}
