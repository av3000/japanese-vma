<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Articles;

use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Shared\Enums\ArticleSortField;
use App\Domain\Shared\Enums\SortDirection;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArticleSortCriteriaTest extends TestCase
{
    public function test_a_bare_field_sorts_ascending(): void
    {
        $sort = ArticleSortCriteria::fromSigned('title_jp');

        $this->assertSame(ArticleSortField::TITLE_JP, $sort->field);
        $this->assertFalse($sort->isDescending());
        $this->assertSame('title_jp', $sort->toSigned());
    }

    public function test_a_leading_minus_sorts_descending(): void
    {
        $sort = ArticleSortCriteria::fromSigned('-created_at');

        $this->assertSame(ArticleSortField::CREATED_AT, $sort->field);
        $this->assertTrue($sort->isDescending());
        $this->assertSame('-created_at', $sort->toSigned());
    }

    public function test_the_default_is_newest_first(): void
    {
        $this->assertSame('-created_at', ArticleSortCriteria::default()->toSigned());
        $this->assertSame('-created_at', ArticleSortCriteria::fromSignedOrDefault(null)->toSigned());
        $this->assertSame('-created_at', ArticleSortCriteria::fromSignedOrDefault('')->toSigned());
    }

    /**
     * views_total has never been backed by a real column. It must be rejected as
     * input, not turned into SQL.
     */
    #[DataProvider('unsupportedSortProvider')]
    public function test_unsupported_sorts_are_rejected(string $signed): void
    {
        $this->expectException(ValueObjectValidationException::class);

        ArticleSortCriteria::fromSigned($signed);
    }

    public static function unsupportedSortProvider(): array
    {
        return [
            'popularity' => ['views_total'],
            'signed popularity' => ['-views_total'],
            'bare id is not product-facing' => ['id'],
            'unknown column' => ['content_jp'],
            'sql injection attempt' => ['created_at; DROP TABLE articles'],
            'lone minus' => ['-'],
        ];
    }

    public function test_every_advertised_value_round_trips(): void
    {
        foreach (ArticleSortCriteria::allowedValues() as $value) {
            $this->assertSame($value, ArticleSortCriteria::fromSigned($value)->toSigned());
        }
    }

    public function test_the_advertised_set_is_the_four_fields_in_both_directions(): void
    {
        $this->assertCount(8, ArticleSortCriteria::allowedValues());
    }

    /**
     * Internal callers may order by id; the public contract may not. byId() is the
     * only door, and it never appears in allowedValues().
     */
    public function test_by_id_is_internal_only(): void
    {
        $ascending = ArticleSortCriteria::byId();
        $descending = ArticleSortCriteria::byId(SortDirection::DESC);

        $this->assertSame(ArticleSortField::ID, $ascending->field);
        $this->assertFalse($ascending->isDescending());
        $this->assertTrue($descending->isDescending());
        $this->assertNotContains('id', ArticleSortCriteria::allowedValues());
        $this->assertNotContains('-id', ArticleSortCriteria::allowedValues());
    }
}
