<?php

namespace Tests\Unit\Pdf;

use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The PDF layouts declare their CJK faces by absolute path and dompdf does not scan system
 * fonts: an @font-face src it cannot open is dropped silently and the text falls back to the
 * built-in Helvetica, which has no Japanese glyphs. The failure is invisible in code review
 * and in every test that only asserts on view names - it only shows up as an empty PDF.
 *
 * These paths come from font packages installed in .docker/Dockerfile and .docker/Dockerfile.ci.
 * This test is what makes an image that forgets one of them fail instead of ship.
 */
class PdfFontAvailabilityTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function pdfLayoutProvider(): array
    {
        return [
            'catalogue layout' => ['pdf.catalogues.layout'],
            'article layout' => ['pdf.articles.layout'],
        ];
    }

    /**
     * @dataProvider pdfLayoutProvider
     */
    public function test_pdf_layout_font_faces_resolve_to_files_on_disk(string $layout): void
    {
        $paths = $this->fontFaceSourcePaths($layout);

        $this->assertNotEmpty(
            $paths,
            "{$layout} declares no @font-face src - Japanese text has no font to render with.",
        );

        foreach ($paths as $path) {
            $this->assertFileExists(
                $path,
                "{$layout} declares a font at {$path} that is missing from this image. "
                .'Install the package that provides it in .docker/Dockerfile and .docker/Dockerfile.ci.',
            );
        }
    }

    /**
     * @return list<string>
     */
    private function fontFaceSourcePaths(string $layout): array
    {
        $this->assertTrue(View::exists($layout), "View {$layout} does not exist.");

        $html = View::make($layout)->render();

        preg_match_all("/src:\s*url\(['\"]?([^'\")]+)['\"]?\)/", $html, $matches);

        return array_values(array_unique($matches[1]));
    }
}
