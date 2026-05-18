<?php

namespace App\Shared\Http;

use App\Domain\Pdf\DTOs\PdfRenderResult;
use Illuminate\Http\Response;

class PdfResponseFactory
{
    public function make(PdfRenderResult $pdf): Response
    {
        return response($pdf->contents, 200, [
            'Content-Type' => $pdf->mimeType,
            'Content-Length' => (string) $pdf->contentLength,
            'Content-Disposition' => sprintf('%s; filename="%s"', $pdf->disposition->value, $pdf->filename),
        ]);
    }
}
