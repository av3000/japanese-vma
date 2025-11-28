<?php

namespace App\Application\Users\Services;

use App\Domain\Shared\Enums\UserRole;
use App\Domain\Shared\ValueObjects\{EntityId, UserId};

interface RoleServiceInterface
{
    public function userHasRole(EntityId $userUuid, string|UserRole $role): bool;
    public function isAdmin(EntityId $userUuid): bool;
    public function assignRole(EntityId $userUuid, string|UserRole $role): void;
    public function removeRole(EntityId $userUuid, string|UserRole $role): void;
    public function getUserRoles(EntityId $userUuid): array;
}
