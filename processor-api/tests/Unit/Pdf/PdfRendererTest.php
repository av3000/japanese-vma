<?php

namespace Tests\Unit\Pdf;

use App\Application\Pdf\PdfRendererInterface;
use App\Domain\Pdf\DTOs\PdfDocument;
use App\Domain\Pdf\Enums\PdfDisposition;
use App\Infrastructure\Pdf\DompdfPdfRenderer;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PdfRendererTest extends TestCase
{
    public function test_container_binds_pdf_renderer_contract_to_dompdf_renderer(): void
    {
        $renderer = $this->app->make(PdfRendererInterface::class);

        $this->assertInstanceOf(DompdfPdfRenderer::class, $renderer);
    }

    public function test_renderer_returns_pdf_bytes_and_response_metadata(): void
    {
        View::addNamespace('pdf-test', base_path('tests/Fixtures/views'));

        $renderer = $this->app->make(PdfRendererInterface::class);

        $document = new PdfDocument(
            view: 'pdf-test::minimal-pdf',
            data: [
                'title' => 'Japanese PDF test',
                'body' => '日本語と漢字',
            ],
            filename: 'sample.pdf',
            disposition: PdfDisposition::INLINE,
            paper: 'a4',
            orientation: 'portrait',
            options: [
                'defaultFont' => 'Noto Sans CJK JP',
            ],
        );

        $result = $renderer->render($document);

        $this->assertStringStartsWith('%PDF', $result->contents);
        $this->assertSame('sample.pdf', $result->filename);
        $this->assertSame('application/pdf', $result->mimeType);
        $this->assertSame(PdfDisposition::INLINE, $result->disposition);
        $this->assertSame(strlen($result->contents), $result->contentLength);
        $this->assertGreaterThan(0, $result->contentLength);
    }
}
