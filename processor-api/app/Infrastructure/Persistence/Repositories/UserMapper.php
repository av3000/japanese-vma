<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Models\User as PersistenceUser;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Shared\ValueObjects\{EntityId, UserId, Email};
use App\Domain\Shared\ValueObjects\UserName;

class UserMapper
{
    public static function mapToDomain(PersistenceUser $entity): DomainUser
    {
        return new DomainUser(
            id: UserId::from($entity->id),
            uuid: EntityId::from($entity->uuid),
            name: UserName::from($entity->name),
            email: Email::from($entity->email),
            roleName: $entity->role(),
            createdAt: $entity->created_at->toDateTimeImmutable(),
        );
    }
}
