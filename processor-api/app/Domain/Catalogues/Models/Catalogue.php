<?php

declare(strict_types=1);

namespace App\Domain\Catalogues\Models;

use App\Domain\Catalogues\ValueObjects\{CatalogueTitle, CatalogueDescription};
use App\Domain\Shared\Enums\{SavedListType, PublicityStatus};
use App\Domain\Shared\ValueObjects\{EntityId, UserId, UserName};

class Catalogue
{
    public function __construct(
        private ?int $id,
        private EntityId $uuid,
        private SavedListType $type,
        private CatalogueTitle $title,
        private CatalogueDescription $description,
        private PublicityStatus $publicity,
        private UserId $ownerId,
        private UserName $ownerName,
        private EntityId $ownerUuid,
        private \DateTimeImmutable $createdAt,
        private \DateTimeImmutable $updatedAt
    ) {}

    public function getIdValue(): int
    {
        return $this->id;
    }

    public function getUid(): EntityId
    {
        return $this->uuid;
    }

    public function getType(): SavedListType
    {
        return $this->type;
    }

    public function getTypeLabel(): string
    {
        return $this->type->label();
    }

    public function getTitle(): CatalogueTitle
    {
        return $this->title;
    }

    public function getDescription(): CatalogueDescription
    {
        return $this->description;
    }

    public function getPublicity(): PublicityStatus
    {
        return $this->publicity;
    }

    public function getOwnerId(): UserId
    {
        return $this->ownerId;
    }

    public function getOwnerName(): UserName
    {
        return $this->ownerName;
    }

    public function getOwnerUuid(): EntityId
    {
        return $this->ownerUuid;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
