<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class EngagementFilterDTO
{
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $objectType,
    ) {}
}
