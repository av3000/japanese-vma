<?php

namespace App\Application\Pdf;

use App\Application\Pdf\DTOs\PdfExportRequest;
use App\Shared\Results\Result;

interface PdfExportServiceInterface
{
    public function export(PdfExportRequest $request): Result;
}
