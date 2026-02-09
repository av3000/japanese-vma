<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Engagement\Models\Like as DomainLike;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;
use App\Domain\Users\Models\LikeUser;
use App\Infrastructure\Persistence\Models\Like as PersistenceLike;

class LikeMapper
{
    public function mapToDomain(PersistenceLike $entity): DomainLike
    {
        $user = new LikeUser(
            id: new UserId($entity->user->id),
            uuid: new EntityId($entity->user->uuid),
            name: new UserName($entity->user->name)
        );

        return new DomainLike(
            id: $entity->id,
            value: $entity->value,
            created_at: $entity->created_at->toDateTimeImmutable(),
            user: $user,
        );
    }

    public function mapCollectionToDomain($persistenceLikes): array
    {
        return $persistenceLikes
            ->map(fn($like) => $this->mapToDomain($like))
            ->all();
    }
}
