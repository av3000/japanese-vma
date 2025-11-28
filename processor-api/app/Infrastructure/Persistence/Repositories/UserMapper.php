<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\{EntityId, UserId, Email, UserName};
use App\Domain\Users\DTOs\RoleDTO;

class UserMapper
{
    /**
     * Map persistence model to domain model
     * Assumes roles are already eager-loaded by repository
     */
    public static function mapToDomain(PersistenceUser $persistenceUser): DomainUser
    {
        $roles = $persistenceUser->getRoleNames()
            ->map(fn(string $roleName) => RoleDTO::fromName($roleName))
            ->toArray();

        return new DomainUser(
            id: new UserId($persistenceUser->id),
            uuid: new EntityId($persistenceUser->uuid),
            name: new UserName($persistenceUser->name),
            email: new Email($persistenceUser->email),
            roles: $roles,
            createdAt: \DateTimeImmutable::createFromMutable($persistenceUser->created_at),
        );
    }

    /**
     * Mutates the entity with updated values. Used for updates.
     *
     */
    public static function mapToExistingEntity(
        DomainUser $domainUser,
        PersistenceUser $entity
    ): void {
        $entity->uuid = $domainUser->getUuid()->value();
        $entity->name = $domainUser->getName()->value();
        $entity->email = $domainUser->getEmail()->value();
    }
}
