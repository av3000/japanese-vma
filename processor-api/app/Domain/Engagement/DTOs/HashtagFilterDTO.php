<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class HashtagFilterDTO
{
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $entityType,
    ) {}
}
