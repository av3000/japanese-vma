<?php

namespace App\Application\Pdf\DTOs;

use App\Domain\Pdf\Enums\PdfExportKind;
use App\Domain\Pdf\Enums\PdfExportSource;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;

readonly class PdfExportRequest
{
    public function __construct(
        public PdfExportSource $source,
        public EntityId $entityUuid,
        public PdfExportKind $kind,
        public User $viewer,
    ) {
    }
}
