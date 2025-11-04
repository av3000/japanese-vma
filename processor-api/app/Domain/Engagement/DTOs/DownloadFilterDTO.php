<?php
namespace App\Domain\Engagement\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class DownloadFilterDTO
{
    public function __construct(
        public int $entityId,
        public ObjectTemplateType $objectType,
    ) {}
}
