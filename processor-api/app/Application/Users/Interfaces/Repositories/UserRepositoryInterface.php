<?php

namespace App\Application\Users\Interfaces\Repositories;

use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\EntityId;

interface UserRepositoryInterface
{
    /**
     * Find user by UUID with role relationship.
     *
     * @param EntityId $userUuid The user's public UUID
     * @return DomainUser|null The domain user if found, null if not found
     */
    public function findByUuid(EntityId $userUuid): ?DomainUser;
}
