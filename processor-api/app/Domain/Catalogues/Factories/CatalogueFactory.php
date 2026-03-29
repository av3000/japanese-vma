<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\Factories;

use App\Domain\Catalogues\DTOs\CatalogueCreateDTO;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Catalogues\ValueObjects\CatalogueDescription;
use App\Domain\Catalogues\ValueObjects\CatalogueTitle;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\UserId;
use App\Domain\Shared\ValueObjects\UserName;

class CatalogueFactory
{
    public static function createFromDTO(
        CatalogueCreateDTO $dto,
        UserId $ownerId,
        UserName $ownerName,
        EntityId $ownerUuid
    ): Catalogue {
        $now = new \DateTimeImmutable();

        return new Catalogue(
            id: null,
            uuid: EntityId::generate(),
            type: $dto->type,
            title: CatalogueTitle::fromInput($dto->title),
            description: CatalogueDescription::fromInput($dto->description),
            publicity: $dto->publicity ? PublicityStatus::PUBLIC : PublicityStatus::PRIVATE,
            ownerId: $ownerId,
            ownerName: $ownerName,
            ownerUuid: $ownerUuid,
            createdAt: $now,
            updatedAt: $now,
        );
    }
}
