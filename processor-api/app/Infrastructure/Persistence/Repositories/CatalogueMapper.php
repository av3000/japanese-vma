<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Catalogues\Models\Catalogue as DomainCatalogue;
use App\Domain\Catalogues\ValueObjects\{CatalogueTitle, CatalogueDescription};
use App\Domain\Shared\Enums\{SavedListType, PublicityStatus, ObjectTemplateType};
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
            new EntityId($entity->user?->uuid ?? null),
            $entity->created_at->toDateTimeImmutable(),
            $entity->updated_at->toDateTimeImmutable(),
        );
    }

    public function mapToEntity(DomainCatalogue $catalogue): array
    {
        return [
            'uuid' => $catalogue->getUid()->value(),
            'entity_type_uuid' => ObjectTemplateType::LIST->value,
            'user_id' => $catalogue->getOwnerId()->value(),
            'type' => $catalogue->getType()->value,
            'title' => $catalogue->getTitle()->value,
            'description' => (string) $catalogue->getDescription(),
            'publicity' => $catalogue->getPublicity()->value,
            'created_at' => $catalogue->getCreatedAt(),
            'updated_at' => $catalogue->getUpdatedAt(),
        ];
    }

    public function mapToExistingEntity(DomainCatalogue $catalogue, PersistenceCatalogue $entity): void
    {
        $entity->type = $catalogue->getType()->value;
        $entity->title = $catalogue->getTitle()->value;
        $entity->description = (string) $catalogue->getDescription();
        $entity->publicity = $catalogue->getPublicity()->value;
        $entity->updated_at = $catalogue->getUpdatedAt();
    }
}
