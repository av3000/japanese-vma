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
    private const ADAPTER = 'app/Infrastructure/Translations/LaravelEnglishSourceCatalogue.php';

    /** Ways a PHP file could read the `ui` group without the adapter. */
    private const FORBIDDEN_PATTERNS = [
        '/lang\/en\/ui/',
        '/load\(\s*[\'"]en[\'"]\s*,\s*[\'"]ui[\'"]/',
        '/(?<![\w])(?:__|trans|trans_choice)\(\s*[\'"]ui[.\'"]/',
        '/Lang::(?:get|has|choice)\(\s*[\'"]ui[.\'"]/',
        '/->(?:get|has|choice)\(\s*[\'"]ui[.\'"]/', // app('translator')->get('ui.x'), trans()->get('ui.x')
    ];

    /** Where PHP or Blade code lives. `resources/lang` is the catalogue itself and is not scanned. */
    private const SCANNED = ['app', 'routes', 'config', 'database', 'resources/views'];

    public function test_only_the_adapter_reads_the_english_ui_catalogue(): void
    {
        $root = dirname(__DIR__, 3);
        $violations = [];

        foreach (self::SCANNED as $directory) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator("{$root}/{$directory}", RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));

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
        }

        $this->assertSame([], $violations, "Read the catalogue through EnglishSourceCatalogueInterface:\n".implode("\n", $violations));
    }

    public function test_the_patterns_catch_each_way_of_reading_the_catalogue(): void
    {
        $readers = [
            "include resource_path('lang/en/ui.php');",
            "\$loader->load('en', 'ui');",
            "{{ __('ui.account.log_in') }}",
            "trans('ui.account.log_in')",
            "Lang::get('ui.account.log_in')",
            "app('translator')->get('ui.account.log_in')",
            "trans()->get('ui.account.log_in')",
        ];

        foreach ($readers as $source) {
            $matched = array_filter(self::FORBIDDEN_PATTERNS, static fn (string $pattern): bool => preg_match($pattern, $source) === 1);

            $this->assertNotEmpty($matched, "No pattern catches: {$source}");
        }

        foreach (["__('validation.required')", "\$request->get('uid')", "trans('auth.failed')"] as $innocent) {
            foreach (self::FORBIDDEN_PATTERNS as $pattern) {
                $this->assertSame(0, preg_match($pattern, $innocent), "{$pattern} flags: {$innocent}");
            }
        }
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
