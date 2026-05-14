<?php

namespace App\Domain\Pdf\Errors;

use App\Shared\Enums\HttpStatus;
use App\Shared\Results\ResultError;

class PdfExportErrors
{
    public static function unsupported(string $source, string $kind): ResultError
    {
        return new ResultError(
            code: 'PdfExport.Unsupported',
            status: HttpStatus::BAD_REQUEST,
            description: 'Unsupported PDF export',
            detail: "PDF export {$source}/{$kind} is not supported",
            errorMessage: "PDF export {$source}/{$kind} is not supported",
        );
    }

    public static function renderFailed(string $errorMessage): ResultError
    {
        return new ResultError(
            code: 'PdfExport.RenderFailed',
            status: HttpStatus::INTERNAL_SERVER_ERROR,
            description: 'PDF export failed',
            detail: 'An unexpected error occurred while rendering the PDF',
            errorMessage: $errorMessage,
        );
    }
}
