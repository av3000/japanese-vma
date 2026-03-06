<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Catalogues\Models\Catalogue as DomainCatalogue;
use App\Domain\Catalogues\ValueObjects\{CatalogueTitle, CatalogueDescription};
use App\Domain\Shared\Enums\{SavedListType, PublicityStatus};
use App\Domain\Shared\ValueObjects\{EntityId, UserId, UserName};
use App\Infrastructure\Persistence\Models\Catalogue as PersistenceCatalogue;

class CatalogueMapper
{
    public function mapToDomain(PersistenceCatalogue $entity): DomainCatalogue
    {
        $type = $entity->type instanceof SavedListType
            ? $entity->type
            : SavedListType::from((int) $entity->type);

        return new DomainCatalogue(
            $entity->id,
            new EntityId($entity->uuid),
            $type,
            CatalogueTitle::fromInput($entity->title),
            CatalogueDescription::fromInput($entity->description),
            PublicityStatus::from((int) $entity->publicity),
            new UserId($entity->user_id),
            new UserName($entity->user?->name ?? 'Unknown User'),
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable(),
        );
    }
}
