<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class ViewFilterDTO
{
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $objectType,
        public ?int $userId = null,
        public ?string $ipAddress = null
    ) {}
}
