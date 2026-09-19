<?php

namespace App\Domain\Pdf\DTOs;

use App\Domain\Catalogues\Models\Catalogue;
use DateTimeImmutable;

/**
 * The catalogue identity block every catalogue PDF layout prints above its table.
 */
readonly class CataloguePdfHeaderDTO
{
    public function __construct(
        public int $id,
        public string $uuid,
        public string $title,
        public string $typeLabel,
        public string $author,
        public int $userId,
        public DateTimeImmutable $date,
    ) {
    }

    public static function fromCatalogue(Catalogue $catalogue): self
    {
        return new self(
            id: $catalogue->getIdValue(),
            uuid: $catalogue->getUid()->value(),
            title: $catalogue->getTitle()->value,
            typeLabel: $catalogue->getTypeLabel(),
            author: $catalogue->getOwnerName()->value(),
            userId: $catalogue->getOwnerId()->value(),
            date: $catalogue->getCreatedAt(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'type_label' => $this->typeLabel,
            'author' => $this->author,
            'user_id' => $this->userId,
            'date' => $this->date,
        ];
    }
}
