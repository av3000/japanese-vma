<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\Unit\Architecture\Support\AdminBoundaryRules;
use Tests\Unit\Architecture\Support\ClassReferenceScanner;

/**
 * Staff actions live in the admin panel only, and the website knows nothing about them
 * (`ARCH-admin-boundary`). The checks run on resolved class references, not on text, so comments
 * do not count and aliased or grouped imports do.
 *
 * - Website code (app/Http, app/Console, app/Jobs and the non-moderation parts of app/Application)
 *   never reaches a moderation service or policy, or a panel-only use case.
 * - The panel (app/Filament) never acts through the website's write services or policies, so
 *   every staff action has one entry point and one activity log entry.
 * - The website's policies grant staff nothing.
 *
 * KNOWN_VIOLATIONS lists what is still being removed, each with its rule and the issue that removes
 * it. An entry that no longer violates fails the test too, so the list only shrinks.
 */
class AdminBoundaryTest extends TestCase
{
    /** @var array<string, array{rule: 'moderation'|'staff', issue: string}> path relative to app/ */
    private const KNOWN_VIOLATIONS = [
        'Http/v1/Articles/Controllers/ArticleController.php' => ['rule' => 'moderation', 'issue' => '#572'],
        'Application/Articles/Policies/ArticlePolicy.php' => ['rule' => 'staff', 'issue' => '#572'],
        'Application/Community/Posts/Policies/PostPolicy.php' => ['rule' => 'staff', 'issue' => '#573'],
        'Application/Catalogues/Policies/CataloguePolicy.php' => ['rule' => 'staff', 'issue' => '#574'],
        'Application/Comments/Policies/CommentPolicy.php' => ['rule' => 'staff', 'issue' => '#574'],
        'Application/JapaneseMaterial/Sentences/Policies/SentencePolicy.php' => ['rule' => 'staff', 'issue' => '#574'],
    ];

    private AdminBoundaryRules $rules;

    protected function setUp(): void
    {
        $this->rules = new AdminBoundaryRules;
    }

    public function test_the_website_never_reaches_moderation_code(): void
    {
        $violations = [];

        foreach ($this->filesUnder('Http', 'Console', 'Jobs', 'Application') as $relative) {
            if (AdminBoundaryRules::isWebsiteFile($relative) && ! $this->isKnown($relative)) {
                array_push($violations, ...$this->rules->websiteReachingModeration($relative, $this->code($relative)));
            }
        }

        $this->assertNoViolations($violations);
    }

    public function test_the_panel_never_acts_through_the_website_write_path(): void
    {
        $violations = [];

        foreach ($this->filesUnder('Filament') as $relative) {
            array_push($violations, ...$this->rules->panelUsingWebsiteWritePath($relative, $this->code($relative)));
        }

        $this->assertNoViolations($violations);
    }

    /**
     * Panel reads go through *ReaderInterface ports or named model scopes, never the DB facade.
     * The Environment page only asks the facade for the driver name.
     */
    public function test_the_panel_never_queries_through_the_db_facade(): void
    {
        $allowed = ['Filament/Pages/EnvironmentHealth.php'];
        $scanner = new ClassReferenceScanner;
        $violations = [];

        foreach ($this->filesUnder('Filament') as $relative) {
            if (in_array($relative, $allowed, true)) {
                continue;
            }

            foreach ($scanner->references($this->code($relative))['Illuminate\Support\Facades\DB'] ?? [] as $line) {
                $violations[] = "{$relative}:{$line} queries through the DB facade";
            }
        }

        $this->assertSame([], $violations, "Use a reader or a model scope instead:\n".implode("\n", $violations));
    }

    /**
     * The panel uses Eloquent models for its tables and forms (epic #179 rule 2) and nothing else
     * from Infrastructure. PanelActor is the one exception: CurrentUserProviderInterface resolves
     * only the `api` guard, so it maps the `web` panel user through the provider's static method.
     */
    public function test_the_panel_reaches_infrastructure_only_through_models(): void
    {
        $allowed = ['Filament/Support/PanelActor.php' => 'App\Infrastructure\Auth\Providers\PassportCurrentUserProvider'];
        $scanner = new ClassReferenceScanner;
        $violations = [];

        foreach ($this->filesUnder('Filament') as $relative) {
            foreach ($scanner->references($this->code($relative)) as $class => $lines) {
                if (! str_starts_with($class, 'App\Infrastructure\\')
                    || str_starts_with($class, 'App\Infrastructure\Persistence\Models\\')
                    || ($allowed[$relative] ?? null) === $class) {
                    continue;
                }

                $violations[] = "{$relative}:{$lines[0]} reaches {$class}";
            }
        }

        $this->assertSame([], $violations, "Go through an application port instead:\n".implode("\n", $violations));
    }

    public function test_website_policies_grant_staff_nothing(): void
    {
        $violations = [];

        foreach (AdminBoundaryRules::WEBSITE_POLICIES as $relative) {
            if (! $this->isKnown($relative)) {
                array_push($violations, ...$this->rules->staffChecks($relative, $this->code($relative)));
            }
        }

        $this->assertNoViolations($violations);
    }

    public function test_every_known_violation_still_exists(): void
    {
        $stale = [];

        foreach (self::KNOWN_VIOLATIONS as $relative => ['rule' => $rule, 'issue' => $issue]) {
            $found = $rule === 'moderation'
                ? $this->rules->websiteReachingModeration($relative, $this->code($relative))
                : $this->rules->staffChecks($relative, $this->code($relative));

            if ($found === []) {
                $stale[] = "{$relative} ({$issue})";
            }
        }

        $this->assertSame([], $stale, "Fixed, so remove from KNOWN_VIOLATIONS:\n".implode("\n", $stale));
    }

    public function test_the_rules_catch_what_a_line_scan_missed(): void
    {
        $panelUsingConcreteWriteService = <<<'PHP'
            <?php
            namespace App\Filament\Resources\Posts\Actions;

            use App\Application\Community\Posts\Services\PostWriteService;

            final class Example
            {
                public function __construct(private PostWriteService $posts)
                {
                }
            }
            PHP;

        $consoleReachingPanelOnlyCode = <<<'PHP'
            <?php
            namespace App\Console\Commands;

            use App\Application\Engagement\Actions as Hashtags;

            final class Example
            {
                public function handle(): void
                {
                    app(Hashtags\MergeHashtagsAction::class);
                }
            }
            PHP;

        $policyGrantingAdminByRole = <<<'PHP'
            <?php
            namespace App\Application\Articles\Policies;

            use App\Domain\Shared\Enums\UserRole;

            final class ArticlePolicy
            {
                // Staff used to bypass this with ->isAdmin.
                public function canDelete(object $user): bool
                {
                    return $user->hasRole(UserRole::ADMIN->value);
                }
            }
            PHP;

        self::assertSame(
            [
                'Filament/Example.php:4 acts through the website write path: App\Application\Community\Posts\Services\PostWriteService',
                'Filament/Example.php:8 acts through the website write path: App\Application\Community\Posts\Services\PostWriteService',
            ],
            $this->rules->panelUsingWebsiteWritePath('Filament/Example.php', $panelUsingConcreteWriteService),
        );
        self::assertSame(
            ['Console/Example.php:10 reaches staff-only code: App\Application\Engagement\Actions\MergeHashtagsAction'],
            $this->rules->websiteReachingModeration('Console/Example.php', $consoleReachingPanelOnlyCode),
        );
        self::assertSame(
            [
                'Policy.php:11 grants staff through ->hasRole',
                'Policy.php:11 grants staff through UserRole::ADMIN',
            ],
            $this->rules->staffChecks('Policy.php', $policyGrantingAdminByRole),
        );
    }

    private function isKnown(string $relative): bool
    {
        return array_key_exists($relative, self::KNOWN_VIOLATIONS);
    }

    /**
     * @param list<string> $violations
     */
    private function assertNoViolations(array $violations): void
    {
        $this->assertSame(
            [],
            $violations,
            "Staff actions belong to the admin panel and its moderation services (ARCH-admin-boundary):\n".implode("\n", $violations),
        );
    }

    private function code(string $relative): string
    {
        return (string) file_get_contents($this->appPath().'/'.$relative);
    }

    /**
     * @return list<string> paths relative to app/
     */
    private function filesUnder(string ...$directories): array
    {
        $files = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->appPath().'/'.$directory, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($iterator as $file) {
                if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                    $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($this->appPath()) + 1));
                }
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
