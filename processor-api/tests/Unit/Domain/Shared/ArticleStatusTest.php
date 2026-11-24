<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Shared;

use App\Domain\Shared\Enums\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function test_only_processed_and_approved_articles_are_publicly_visible(): void
    {
        self::assertSame(
            [ArticleStatus::PROCESSED, ArticleStatus::APPROVED],
            ArticleStatus::publiclyVisible(),
        );

        self::assertTrue(ArticleStatus::PROCESSED->isPubliclyVisible());
        self::assertTrue(ArticleStatus::APPROVED->isPubliclyVisible());
        self::assertFalse(ArticleStatus::PENDING->isPubliclyVisible());
        self::assertFalse(ArticleStatus::REVIEWING->isPubliclyVisible());
        self::assertFalse(ArticleStatus::REJECTED->isPubliclyVisible());
    }

    public function test_publicly_visible_values_match_the_enum_cases(): void
    {
        self::assertSame([1, 4], ArticleStatus::publiclyVisibleValues());
    }
}
