<?php

declare(strict_types=1);

namespace App\Domain\Users\Queries;

use App\Domain\Shared\ValueObjects\UserId;

/**
 * Data Transfer Object for querying roles.
 * Acts as a flexible filter for role queries.
 */
final readonly class RoleQueryCriteria
{
    /**
     * @param UserId|null $userId Filter roles assigned to this user ID.
     * @param string|null $roleName Filter by specific role name.
     * @param string|null $roleId Filter by specific role id.
     * // Add other potential filters here (e.g., hasPermission: string|null)
     */
    public function __construct(
        public readonly ?UserId $userId = null,
        public readonly ?string $roleName = null,
        public readonly ?string $guardName = null,
    ) {}

    public static function forUser(UserId $userId): self
    {
        return new self(userId: $userId);
    }

    public static function withRoleName(string $roleName): self
    {
        return new self(roleName: $roleName);
    }
}
