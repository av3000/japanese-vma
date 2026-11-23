<?php

namespace App\Providers;

use App\Application\Pdf\PdfRendererInterface;
use App\Infrastructure\Pdf\DompdfPdfRenderer;
use Illuminate\Support\ServiceProvider;

class PdfServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PdfRendererInterface::class, DompdfPdfRenderer::class);
    }
}
