<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use App\Application\Users\Support\PanelPermission;
use App\Application\Users\Support\PermissionCatalog;
use Tests\TestCase;

/**
 * The enum is what code checks; config/permission-catalog.php is what the seeder writes and the
 * role form shows. They must name exactly the same permissions.
 */
final class PanelPermissionCatalogTest extends TestCase
{
    public function test_the_enum_and_the_catalogue_name_the_same_permissions(): void
    {
        $fromEnum = array_column(PanelPermission::cases(), 'value');
        sort($fromEnum);

        self::assertSame(PermissionCatalog::allPermissionNames(), $fromEnum);
    }

    public function test_the_pruned_permissions_are_gone(): void
    {
        foreach (['articles.create', 'catalogues.create', 'comments.update'] as $name) {
            self::assertNull(PanelPermission::tryFrom($name), $name);
            self::assertNotContains($name, PermissionCatalog::allPermissionNames());
        }
    }
}
