<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Articles;

use App\Domain\Articles\Enums\ArticleOrigin;
use App\Domain\Articles\ValueObjects\ArticleProvenance;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ArticleProvenanceTest extends TestCase
{
    public function test_a_user_article_has_no_source(): void
    {
        $provenance = ArticleProvenance::user();

        self::assertSame(ArticleOrigin::User, $provenance->origin);
        self::assertNull($provenance->contentSourceId);
        self::assertNull($provenance->externalId);
        self::assertFalse($provenance->isImported());
    }

    public function test_an_imported_article_carries_its_source_and_trimmed_external_id(): void
    {
        $provenance = ArticleProvenance::imported(3, '  nd-20260930de53252 ');

        self::assertTrue($provenance->isImported());
        self::assertSame(3, $provenance->contentSourceId);
        self::assertSame('nd-20260930de53252', $provenance->externalId);
    }

    public function test_an_imported_article_needs_an_external_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ArticleProvenance::imported(3, '   ');
    }

    public function test_an_imported_article_needs_a_source(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ArticleProvenance::imported(0, 'nd-1');
    }

    public function test_an_external_id_longer_than_the_column_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ArticleProvenance::imported(3, str_repeat('a', 192));
    }
}
