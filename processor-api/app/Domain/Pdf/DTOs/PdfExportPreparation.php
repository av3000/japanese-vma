<?php

namespace App\Domain\Pdf\DTOs;

readonly class PdfExportPreparation
{
    public function __construct(
        public PdfDocument $document,
        public PdfDownloadTarget $downloadTarget,
    ) {
    }
}
