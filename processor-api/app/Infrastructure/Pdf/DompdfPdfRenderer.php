<?php

namespace App\Infrastructure\Pdf;

use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\DTOs\PdfRenderResult;
use Barryvdh\DomPDF\PDF as DompdfWrapper;
use Illuminate\Contracts\Container\Container;

class DompdfPdfRenderer implements PdfRendererInterface
{
    public function __construct(
        private readonly Container $container,
    ) {
    }

    public function render(PdfDocument $document): PdfRenderResult
    {
        /** @var DompdfWrapper $pdf */
        $pdf = $this->container->make('dompdf.wrapper');

        if ($document->options !== []) {
            $pdf->setOptions($document->options, mergeWithDefaults: true);
        }

        $pdf->setPaper($document->paper, $document->orientation);
        $pdf->loadView($document->view, $document->data);

        return new PdfRenderResult(
            contents: $pdf->output(),
            filename: $document->filename,
            disposition: $document->disposition,
        );
    }
}
