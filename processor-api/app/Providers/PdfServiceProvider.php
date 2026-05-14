<?php

namespace App\Providers;

use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Pdf\PdfExportService;
use App\Application\Pdf\PdfExportServiceInterface;
use App\Application\Pdf\PdfRendererInterface;
use App\Infrastructure\Pdf\DompdfPdfRenderer;
use Illuminate\Support\ServiceProvider;

class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PdfRendererInterface::class, DompdfPdfRenderer::class);

        $this->app->bind(PdfExportServiceInterface::class, function ($app): PdfExportService {
            return new PdfExportService(
                pdfRenderer: $app->make(PdfRendererInterface::class),
                downloadRepository: $app->make(DownloadRepositoryInterface::class),
                providers: $app->tagged('pdf.export.providers'),
            );
        });
    }
}
