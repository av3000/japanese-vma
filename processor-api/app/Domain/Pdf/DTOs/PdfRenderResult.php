<?php

namespace App\Domain\Pdf\DTOs;

use App\Domain\Pdf\Enums\PdfDisposition;

readonly class PdfRenderResult
{
    public int $contentLength;

    public function __construct(
        public string $contents,
        public string $filename,
        public PdfDisposition $disposition,
        public string $mimeType = 'application/pdf',
    ) {
        $this->contentLength = strlen($contents);
    }
}
