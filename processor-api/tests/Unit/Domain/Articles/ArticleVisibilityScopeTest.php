<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Articles;

use App\Domain\Articles\Enums\ArticleVisibilityMode;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArticleVisibilityScopeTest extends TestCase
{
    public function test_public_only_scope_admits_no_private_owner(): void
    {
        $scope = ArticleVisibilityScope::publicOnly();

        $this->assertSame(ArticleVisibilityMode::PUBLIC_ONLY, $scope->mode);
        $this->assertTrue($scope->isPublicOnly());
        $this->assertFalse($scope->isUnrestricted());
        $this->assertNull($scope->privateOwnerId());
    }

    public function test_public_or_owned_scope_carries_its_owner(): void
    {
        $scope = ArticleVisibilityScope::publicOrOwnedBy(42);

        $this->assertSame(ArticleVisibilityMode::PUBLIC_OR_OWNED, $scope->mode);
        $this->assertFalse($scope->isPublicOnly());
        $this->assertFalse($scope->isUnrestricted());
        $this->assertSame(42, $scope->privateOwnerId());
    }

    /**
     * The invariant that keeps private Articles from leaking: a scope that admits
     * private rows cannot exist without the owner those rows are checked against.
     */
    #[DataProvider('invalidOwnerIdProvider')]
    public function test_public_or_owned_scope_cannot_exist_without_a_real_owner(int $ownerId): void
    {
        $this->expectException(ValueObjectValidationException::class);

        ArticleVisibilityScope::publicOrOwnedBy($ownerId);
    }

    public static function invalidOwnerIdProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    public function test_unrestricted_scope_reports_no_private_owner(): void
    {
        $scope = ArticleVisibilityScope::unrestricted();

        $this->assertSame(ArticleVisibilityMode::UNRESTRICTED, $scope->mode);
        $this->assertTrue($scope->isUnrestricted());
        $this->assertFalse($scope->isPublicOnly());
        $this->assertNull($scope->privateOwnerId());
    }

    /**
     * privateOwnerId() is what the reader branches on, so it must stay null for
     * every mode that is not explicitly public-or-owned.
     */
    public function test_only_the_public_or_owned_mode_exposes_a_private_owner(): void
    {
        $this->assertNull(ArticleVisibilityScope::publicOnly()->privateOwnerId());
        $this->assertNull(ArticleVisibilityScope::unrestricted()->privateOwnerId());
        $this->assertNotNull(ArticleVisibilityScope::publicOrOwnedBy(1)->privateOwnerId());
    }
}
