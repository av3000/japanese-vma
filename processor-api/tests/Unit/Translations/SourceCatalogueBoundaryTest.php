<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * `resources/lang/en/ui.php` has one reader: LaravelEnglishSourceCatalogue. Controllers, services,
 * Filament pages and models go through EnglishSourceCatalogueInterface (AC-4 of #660).
 */
class SourceCatalogueBoundaryTest extends TestCase
{
    private const ADAPTER = 'Infrastructure/Translations/LaravelEnglishSourceCatalogue.php';

    /** Ways a PHP file could read the `ui` group without the adapter. */
    private const FORBIDDEN_PATTERNS = [
        '/lang\/en\/ui/',
        '/load\(\s*[\'"]en[\'"]\s*,\s*[\'"]ui[\'"]/',
        '/(?<![\w])(?:__|trans|trans_choice)\(\s*[\'"]ui[.\'"]/',
        '/Lang::(?:get|has|choice)\(\s*[\'"]ui[.\'"]/',
    ];

    public function test_only_the_adapter_reads_the_english_ui_catalogue(): void
    {
        $app = dirname(__DIR__, 3).'/app';
        $violations = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($app) + 1));

            if ($relative === self::ADAPTER) {
                continue;
            }

            $code = $this->withoutComments((string) file_get_contents($file->getPathname()));

            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                if (preg_match($pattern, $code) === 1) {
                    $violations[] = "{$relative} matches {$pattern}";
                }
            }
        }

        $this->assertSame([], $violations, "Read the catalogue through EnglishSourceCatalogueInterface:\n".implode("\n", $violations));
    }

    /** Docblocks and comments may name the file; only code can read it. */
    private function withoutComments(string $source): string
    {
        $code = '';

        foreach (token_get_all($source) as $token) {
            if (is_array($token)) {
                if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                    continue;
                }

                $code .= $token[1];

                continue;
            }

            $code .= $token;
        }

        return $code;
    }
}
