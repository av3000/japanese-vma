<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\EntityId;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find user by UUID with role relationship.
     *
     * @param EntityId $userUuid The user's public UUID
     * @return DomainUser|null The domain user if found, null if not found
     */
    public function findByUuid(EntityId $userUuid): ?DomainUser
    {
        $persistenceUser = PersistenceUser::with('roles')
            ->where('uuid', $userUuid->value())
            ->first();

        return $persistenceUser ? UserMapper::mapToDomain($persistenceUser) : null;
    }
}
