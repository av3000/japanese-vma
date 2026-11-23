<?php

namespace Tests\Unit\Pdf;

use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The PDF layouts declare their CJK faces by absolute path and dompdf does not scan system
 * fonts: an @font-face src it cannot open is dropped silently and the text falls back to the
 * built-in Helvetica, which has no Japanese glyphs. The failure is invisible in code review
 * and in every test that only asserts on view names - it only shows up as an empty PDF, which
 * is how .docker/Dockerfile.ci shipped for as long as it did (#317).
 *
 * The guard is therefore a consistency check between the paths the layouts declare and the
 * font packages the images install, which holds anywhere. Asserting the files exist only makes
 * sense inside an image that is supposed to have them - the CI suite runs on a bare
 * ubuntu-latest runner with setup-php and no fonts at all.
 */
class PdfFontAvailabilityTest extends TestCase
{
    /**
     * Font directory prefix => the Debian package that provides it.
     *
     * A path with no entry here fails the test rather than passing silently: adding a font to a
     * layout has to be a deliberate decision about what the images install.
     */
    private const FONT_PACKAGES = [
        '/usr/share/fonts/truetype/hanazono/' => 'fonts-hanazono',
        '/usr/share/fonts/opentype/ipafont-gothic/' => 'fonts-ipafont-gothic',
        '/usr/share/fonts/opentype/noto/' => 'fonts-noto-cjk',
        '/usr/share/fonts/truetype/noto/' => 'fonts-noto-cjk',
    ];

    private const DOCKERFILES = [
        '.docker/Dockerfile',
        '.docker/Dockerfile.ci',
    ];

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

    #[DataProvider('pdfLayoutProvider')]
    public function test_every_declared_font_is_installed_by_every_image(string $layout): void
    {
        $paths = $this->fontFaceSourcePaths($layout);

        $this->assertNotEmpty(
            $paths,
            "{$layout} declares no @font-face src - Japanese text has no font to render with.",
        );

        foreach ($paths as $path) {
            $package = $this->packageProviding($path);

            $this->assertNotNull(
                $package,
                "{$layout} declares a font at {$path} that no known package provides. Add its "
                .'Debian package to PdfFontAvailabilityTest::FONT_PACKAGES and to the images.',
            );

            foreach (self::DOCKERFILES as $dockerfile) {
                $this->assertStringContainsString(
                    $package,
                    (string) file_get_contents(base_path($dockerfile)),
                    "{$dockerfile} does not install {$package}, which provides the font {$layout} "
                    .'declares. dompdf drops an unreadable @font-face src and renders Japanese '
                    .'text with a glyph-less fallback.',
                );
            }
        }
    }

    #[DataProvider('pdfLayoutProvider')]
    public function test_declared_fonts_are_readable_inside_the_application_image(string $layout): void
    {
        // Both Dockerfiles set this workdir; anywhere else (a bare CI runner, a developer's host)
        // the absolute font paths are not expected to resolve and the check above is the guard.
        if (base_path() !== '/var/www/html') {
            $this->markTestSkipped('Not running inside the application image.');
        }

        foreach ($this->fontFaceSourcePaths($layout) as $path) {
            $this->assertFileExists(
                $path,
                "{$layout} declares a font at {$path} that is missing from this image, even though "
                .'the Dockerfiles install the package that should provide it.',
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

    private function packageProviding(string $path): ?string
    {
        foreach (self::FONT_PACKAGES as $prefix => $package) {
            if (str_starts_with($path, $prefix)) {
                return $package;
            }
        }

        return null;
    }
}
