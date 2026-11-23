<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture\Support;

/**
 * The admin boundary (`ARCH-admin-boundary`) as rules over resolved class references, so they can
 * run over app/ and over inline snippets alike. Every method takes a file's path relative to app/
 * and its code, and returns "path:line reason" violations.
 */
final class AdminBoundaryRules
{
    /**
     * Panel-only use cases that are not named *Moderation*: the website must not reach them either.
     */
    public const PANEL_ONLY = [
        'App\Application\Engagement\Actions\MergeHashtagsAction',
        'App\Application\Engagement\Actions\RenameHashtagAction',
        'App\Application\Engagement\Policies\HashtagManagementPolicy',
        'App\Application\Articles\Actions\Processing\RequeueArticleProcessingAction',
        'App\Application\Articles\Actions\Processing\RequeueArticleProcessingBatchAction',
        'App\Application\Articles\Policies\ArticleProcessingPolicy',
        'App\Application\Users\Services\UserAdminService',
        'App\Application\Users\Services\UserAdminServiceInterface',
        'App\Application\Users\Policies\UserAdminPolicy',
        'App\Application\Operations\Services\EnvironmentHealthService',
        'App\Application\Operations\Services\EnvironmentHealthServiceInterface',
        'App\Application\Operations\Policies\OperationsPolicy',
    ];

    public const WEBSITE_POLICIES = [
        'Application/Articles/Policies/ArticlePolicy.php',
        'Application/Catalogues/Policies/CataloguePolicy.php',
        'Application/Comments/Policies/CommentPolicy.php',
        'Application/Community/Posts/Policies/PostPolicy.php',
        'Application/JapaneseMaterial/Sentences/Policies/SentencePolicy.php',
    ];

    /** Members whose use in a website policy means "staff may do more". */
    private const STAFF_MEMBERS = ['isAdmin', 'hasRole', 'hasAnyRole', 'isPanelSuperuser', 'checkPermissionTo', 'hasPermissionTo'];

    private const STAFF_CONSTANT = 'App\Domain\Shared\Enums\UserRole::ADMIN';

    private const WEBSITE_WRITE_PATH = '/^App\\\\Application\\\\(Articles|Catalogues|Comments|Community\\\\Posts|JapaneseMaterial\\\\Sentences)\\\\(Services|Policies)\\\\\w+$/';

    public function __construct(private readonly ClassReferenceScanner $scanner = new ClassReferenceScanner)
    {
    }

    public static function isModerationCode(string $class): bool
    {
        return in_array($class, self::PANEL_ONLY, true)
            || preg_match('/^App\\\\Application\\\\.+\\\\\w+Moderation(Service|ServiceInterface|Policy)$/', $class) === 1;
    }

    /**
     * Website code: everything under Http, Console and Jobs, and every Application file that is
     * not moderation or panel-only code itself.
     */
    public static function isWebsiteFile(string $relative): bool
    {
        if (preg_match('#^(Http|Console|Jobs)/#', $relative) === 1) {
            return true;
        }

        return str_starts_with($relative, 'Application/') && ! self::isModerationCode(self::classOf($relative));
    }

    /**
     * @return list<string>
     */
    public function websiteReachingModeration(string $relative, string $code): array
    {
        return $this->violationsWhere(
            $relative,
            $this->scanner->references($code),
            static fn (string $class): bool => self::isModerationCode($class),
            'reaches staff-only code',
        );
    }

    /**
     * @return list<string>
     */
    public function panelUsingWebsiteWritePath(string $relative, string $code): array
    {
        return $this->violationsWhere(
            $relative,
            $this->scanner->references($code),
            static fn (string $class): bool => preg_match(self::WEBSITE_WRITE_PATH, $class) === 1 && ! self::isModerationCode($class),
            'acts through the website write path',
        );
    }

    /**
     * @return list<string>
     */
    public function staffChecks(string $relative, string $code): array
    {
        $violations = [];

        foreach ($this->scanner->memberAccesses($code) as $member => $lines) {
            if (in_array($member, self::STAFF_MEMBERS, true)) {
                foreach ($lines as $line) {
                    $violations[] = "{$relative}:{$line} grants staff through ->{$member}";
                }
            }
        }

        foreach ($this->scanner->constantAccesses($code)[self::STAFF_CONSTANT] ?? [] as $line) {
            $violations[] = "{$relative}:{$line} grants staff through UserRole::ADMIN";
        }

        sort($violations);

        return $violations;
    }

    private static function classOf(string $relative): string
    {
        return 'App\\'.str_replace('/', '\\', substr($relative, 0, -4));
    }

    /**
     * @param array<string, list<int>> $references
     * @param callable(string): bool $forbidden
     *
     * @return list<string>
     */
    private function violationsWhere(string $relative, array $references, callable $forbidden, string $reason): array
    {
        $violations = [];

        foreach ($references as $class => $lines) {
            if ($forbidden($class)) {
                foreach (array_unique($lines) as $line) {
                    $violations[] = "{$relative}:{$line} {$reason}: {$class}";
                }
            }
        }

        sort($violations);

        return $violations;
    }
}
