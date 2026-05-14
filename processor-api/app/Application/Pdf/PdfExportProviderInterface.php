<?php

namespace App\Application\Pdf;

use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Shared\Results\Result;

interface PdfExportProviderInterface
{
    public function supports(PdfExportRequest $request): bool;

    public function prepare(PdfExportRequest $request): Result;
}
