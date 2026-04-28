<?php

declare(strict_types=1);

namespace App\Application\Users\Support;

final class PermissionCatalog
{
    /**
     * @return array<string, array{label: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        /** @var array<string, array{label: string, permissions: array<string, string>}> $groups */
        $groups = config('permission-catalog.groups', []);

        return $groups;
    }

    public static function guardName(): string
    {
        /** @var string $guardName */
        $guardName = config('permission-catalog.guard_name', 'api');

        return $guardName;
    }

    /**
     * @return string[]
     */
    public static function allPermissionNames(): array
    {
        $permissions = [];

        foreach (self::groups() as $group) {
            $permissions = [...$permissions, ...array_keys($group['permissions'])];
        }

        sort($permissions);

        return array_values(array_unique($permissions));
    }

    /**
     * @param array<int, string> $assignedPermissions
     * @return array<string, array<int, string>>
     */
    public static function permissionGroupState(array $assignedPermissions): array
    {
        $assignedLookup = array_fill_keys($assignedPermissions, true);
        $state = [];

        foreach (self::groups() as $groupKey => $group) {
            $state[$groupKey] = array_values(array_filter(
                array_keys($group['permissions']),
                static fn (string $permission): bool => isset($assignedLookup[$permission]),
            ));
        }

        return $state;
    }

    /**
     * @param array<string, array<int, string>> $permissionGroups
     * @return string[]
     */
    public static function flattenPermissionGroups(array $permissionGroups): array
    {
        $permissions = [];

        foreach ($permissionGroups as $groupPermissions) {
            foreach ($groupPermissions as $permission) {
                $permissions[] = $permission;
            }
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return $permissions;
    }
}
