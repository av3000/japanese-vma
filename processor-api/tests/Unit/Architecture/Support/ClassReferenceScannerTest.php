<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture\Support;

use PHPUnit\Framework\TestCase;

final class ClassReferenceScannerTest extends TestCase
{
    private ClassReferenceScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new ClassReferenceScanner;
    }

    public function test_a_class_named_only_in_a_comment_is_not_a_reference(): void
    {
        $references = $this->scanner->references(<<<'PHP'
            <?php
            namespace App\Filament;

            /** Goes through PostWriteService, see App\Application\Community\Posts\Services\PostWriteService. */
            final class Example
            {
                // PostWriteServiceInterface used to be called here.
            }
            PHP);

        self::assertSame([], array_values(array_filter(
            array_keys($references),
            static fn (string $name): bool => str_contains($name, 'PostWrite'),
        )));
    }

    public function test_an_aliased_import_resolves_to_its_full_name(): void
    {
        $references = $this->scanner->references(<<<'PHP'
            <?php
            namespace App\Http\v1\Articles;

            use App\Application\Articles\Services\ArticleModerationServiceInterface as Mod;

            final class Example
            {
                public function __construct(private Mod $moderation)
                {
                }
            }
            PHP);

        self::assertSame([4, 8], $references['App\Application\Articles\Services\ArticleModerationServiceInterface']);
        self::assertArrayNotHasKey('App\Http\v1\Articles\Mod', $references);
    }

    public function test_group_imports_and_fully_qualified_class_constants_resolve(): void
    {
        $references = $this->scanner->references(<<<'PHP'
            <?php
            namespace App\Console\Commands;

            use App\Application\Engagement\Actions\{MergeHashtagsAction, RenameHashtagAction as Rename};

            final class Example
            {
                public function handle(): void
                {
                    app(\App\Application\Users\Services\UserAdminServiceInterface::class);
                    app(Rename::class);
                }
            }
            PHP);

        self::assertArrayHasKey('App\Application\Engagement\Actions\MergeHashtagsAction', $references);
        self::assertSame([4, 11], $references['App\Application\Engagement\Actions\RenameHashtagAction']);
        self::assertSame([10], $references['App\Application\Users\Services\UserAdminServiceInterface']);
    }

    public function test_same_namespace_names_resolve_against_the_namespace(): void
    {
        $references = $this->scanner->references(<<<'PHP'
            <?php
            namespace App\Application\Articles\Services;

            final class Example
            {
                public function __construct(private ArticleModerationService $moderation)
                {
                }

                public function name(): string
                {
                    return $this->moderation->name();
                }
            }
            PHP);

        self::assertArrayHasKey('App\Application\Articles\Services\ArticleModerationService', $references);
        self::assertArrayNotHasKey('App\Application\Articles\Services\name', $references);
    }

    public function test_member_and_constant_accesses_are_reported_separately(): void
    {
        $code = <<<'PHP'
            <?php
            namespace App\Application\Articles\Policies;

            use App\Domain\Shared\Enums\UserRole;

            final class Example
            {
                public function check(object $user): bool
                {
                    return $user?->isAdmin || $user->hasRole(UserRole::ADMIN->value);
                }
            }
            PHP;

        self::assertSame(['isAdmin', 'hasRole', 'value'], array_keys($this->scanner->memberAccesses($code)));
        self::assertSame([10], $this->scanner->constantAccesses($code)['App\Domain\Shared\Enums\UserRole::ADMIN']);
    }
}
