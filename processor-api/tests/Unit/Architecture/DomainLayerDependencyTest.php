<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * app/Domain is the innermost layer. It may depend on itself and on app/Shared,
 * never on the layers that depend on it.
 *
 * Illuminate imports inside Domain are a known, older debt (paginator-wrapping
 * collections, Str helpers) and are not asserted here; the point of this test is
 * to stop the dependency direction between the application's own layers from
 * inverting again, which happened once in #282 and was only caught in review.
 */
class DomainLayerDependencyTest extends TestCase
{
    private const FORBIDDEN = [
        'App\\Application\\',
        'App\\Infrastructure\\',
        'App\\Http\\',
    ];

    public function test_domain_does_not_import_outer_layers(): void
    {
        $violations = [];

        foreach ($this->domainFiles() as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($this->appPath()) + 1));

            foreach (file($file->getPathname()) as $number => $line) {
                foreach (self::FORBIDDEN as $namespace) {
                    if (str_contains($line, $namespace)) {
                        $violations[] = sprintf('%s:%d imports %s', $relative, $number + 1, trim($line));
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "app/Domain must not depend on Application, Infrastructure or Http:\n".implode("\n", $violations),
        );
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function domainFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appPath().'/Domain', RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                yield $file;
            }
        }
    }

    private function appPath(): string
    {
        return dirname(__DIR__, 3).'/app';
    }
}
