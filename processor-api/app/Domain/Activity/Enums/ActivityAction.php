<?php

declare(strict_types=1);

namespace App\Domain\Activity\Enums;

/**
 * The fixed vocabulary of privileged mutations. Stored as the string value.
 */
enum ActivityAction: string
{
    case RoleCreated = 'role.created';
    case RoleUpdated = 'role.updated';
    case RoleDeleted = 'role.deleted';
    case UserCreated = 'user.created';
    case UserUpdated = 'user.updated';
    case UserRoleAssigned = 'user.role_assigned';
    case UserRoleRemoved = 'user.role_removed';
    case UserEmailVerified = 'user.email_verified';
    case UserTokensRevoked = 'user.tokens_revoked';

    public function label(): string
    {
        return match ($this) {
            self::UserEmailVerified => 'User email marked verified',
            self::UserTokensRevoked => 'User signed out everywhere',
            self::RoleCreated => 'Role created',
            self::RoleUpdated => 'Role updated',
            self::RoleDeleted => 'Role deleted',
            self::UserCreated => 'User created',
            self::UserUpdated => 'User updated',
            self::UserRoleAssigned => 'Role assigned to user',
            self::UserRoleRemoved => 'Role removed from user',
        };
    }
}
