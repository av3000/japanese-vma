<?php

declare(strict_types=1);

namespace App\Domain\Activity\Enums;

/**
 * What an activity row is about. Each type carries an allowlist of the attributes that may appear
 * in its before/after snapshot; anything else is dropped, so a new column never leaks into the log
 * (a password hash, say) by default.
 */
enum ActivitySubjectType: string
{
    case Role = 'role';
    case User = 'user';

    /**
     * @return list<string>
     */
    public function loggableAttributes(): array
    {
        return match ($this) {
            self::Role => ['name', 'guard_name', 'permissions'],
            self::User => ['name', 'email', 'email_verified_at', 'roles'],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Role => 'Role',
            self::User => 'User',
        };
    }
}
