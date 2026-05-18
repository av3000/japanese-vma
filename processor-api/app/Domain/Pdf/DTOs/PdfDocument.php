<?php

namespace App\Domain\Pdf\DTOs;

use App\Domain\Pdf\Enums\PdfDisposition;

readonly class PdfDocument
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $view,
        public array $data,
        public string $filename,
        public PdfDisposition $disposition = PdfDisposition::INLINE,
        public string $paper = 'a4',
        public string $orientation = 'portrait',
        public array $options = [],
    ) {
    }
}
