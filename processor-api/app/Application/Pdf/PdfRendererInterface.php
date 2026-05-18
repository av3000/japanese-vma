<?php

namespace App\Application\Pdf;

use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;

interface PdfRendererInterface
{
    public function render(PdfDocument $document): PdfRenderResult;
}
