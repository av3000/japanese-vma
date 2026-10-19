<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Staff actions live in the admin panel only, and the website knows nothing about them.
 *
 * - The website (app/Http) never reaches a moderation service or moderation policy.
 * - The panel (app/Filament) never acts through the website's write services or policies, so
 *   every staff action has one entry point and one activity log entry.
 * - The website's policies grant staff nothing.
 *
 * KNOWN_VIOLATIONS lists what is still being removed, each with the issue that removes it. An
 * entry that no longer violates fails the test too, so the list only shrinks.
 */
class AdminBoundaryTest extends TestCase
{
    private const MODERATION_CODE = '/\b\w+Moderation(Service|ServiceInterface|Policy)\b/';

    private const WEBSITE_WRITE_CODE = '/\b(ArticleServiceInterface|CatalogueServiceInterface|CommentServiceInterface|PostWriteServiceInterface|SentenceServiceInterface|ArticlePolicy|CataloguePolicy|CommentPolicy|PostPolicy|SentencePolicy)\b/';

    private const STAFF_CHECK = '/->isAdmin\b/';

    private const WEBSITE_POLICIES = [
        'Application/Articles/Policies/ArticlePolicy.php',
        'Application/Catalogues/Policies/CataloguePolicy.php',
        'Application/Comments/Policies/CommentPolicy.php',
        'Application/Community/Posts/Policies/PostPolicy.php',
        'Application/JapaneseMaterial/Sentences/Policies/SentencePolicy.php',
    ];

    /** @var array<string, string> path relative to app/ => issue that removes the violation */
    private const KNOWN_VIOLATIONS = [
        'Http/v1/Articles/Controllers/ArticleController.php' => '#572',
        'Application/Articles/Policies/ArticlePolicy.php' => '#572',
        'Application/Community/Posts/Policies/PostPolicy.php' => '#573',
        'Application/Catalogues/Policies/CataloguePolicy.php' => '#574',
        'Application/Comments/Policies/CommentPolicy.php' => '#574',
        'Application/JapaneseMaterial/Sentences/Policies/SentencePolicy.php' => '#574',
    ];

    public function test_the_website_never_reaches_moderation_code(): void
    {
        $this->assertNoViolations($this->violations($this->filesUnder('Http'), self::MODERATION_CODE));
    }

    public function test_the_panel_never_acts_through_the_website_write_path(): void
    {
        $this->assertNoViolations($this->violations($this->filesUnder('Filament'), self::WEBSITE_WRITE_CODE));
    }

    public function test_website_policies_grant_staff_nothing(): void
    {
        $this->assertNoViolations($this->violations(self::WEBSITE_POLICIES, self::STAFF_CHECK));
    }

    public function test_every_known_violation_still_exists(): void
    {
        $stale = [];

        foreach (array_keys(self::KNOWN_VIOLATIONS) as $relative) {
            $pattern = str_starts_with($relative, 'Http/') ? self::MODERATION_CODE : self::STAFF_CHECK;

            if ($this->matchingLines($relative, $pattern) === []) {
                $stale[] = $relative;
            }
        }

        $this->assertSame([], $stale, "Fixed, so remove from KNOWN_VIOLATIONS:\n".implode("\n", $stale));
    }

    /**
     * @param list<string> $files paths relative to app/
     *
     * @return list<string>
     */
    private function violations(array $files, string $pattern): array
    {
        $violations = [];

        foreach ($files as $relative) {
            if (array_key_exists($relative, self::KNOWN_VIOLATIONS)) {
                continue;
            }

            foreach ($this->matchingLines($relative, $pattern) as $number => $line) {
                $violations[] = sprintf('%s:%d %s', $relative, $number, $line);
            }
        }

        return $violations;
    }

    /**
     * @return array<int, string> line number => trimmed line
     */
    private function matchingLines(string $relative, string $pattern): array
    {
        $matches = [];

        foreach (file($this->appPath().'/'.$relative) as $index => $line) {
            if (preg_match($pattern, $line) === 1) {
                $matches[$index + 1] = trim($line);
            }
        }

        return $matches;
    }

    /**
     * @param list<string> $violations
     */
    private function assertNoViolations(array $violations): void
    {
        $this->assertSame(
            [],
            $violations,
            "Staff actions belong to the admin panel and its moderation services:\n".implode("\n", $violations),
        );
    }

    /**
     * @return list<string> paths relative to app/
     */
    private function filesUnder(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->appPath().'/'.$directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($this->appPath()) + 1));
            }
        }

        sort($files);

        return $files;
    }

    private function appPath(): string
    {
        return dirname(__DIR__, 3).'/app';
    }
}
