<?php

namespace App\Domain\Pdf\DTOs;

use App\Domain\Shared\Enums\ObjectTemplateType;

readonly class PdfDownloadTarget
{
    public function __construct(
        public ObjectTemplateType $objectType,
        public int $entityId,
    ) {
    }
}
