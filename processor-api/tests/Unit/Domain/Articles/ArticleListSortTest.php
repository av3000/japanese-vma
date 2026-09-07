<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Articles;

use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\Shared\Enums\ArticleSortField;
use App\Domain\Shared\Exceptions\ValueObjectValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArticleListSortTest extends TestCase
{
    public function test_a_bare_field_sorts_ascending(): void
    {
        $sort = ArticleListSort::fromSigned('title_jp');

        $this->assertSame(ArticleSortField::TITLE_JP, $sort->field);
        $this->assertFalse($sort->isDescending());
        $this->assertSame('title_jp', $sort->toSigned());
    }

    public function test_a_leading_minus_sorts_descending(): void
    {
        $sort = ArticleListSort::fromSigned('-created_at');

        $this->assertSame(ArticleSortField::CREATED_AT, $sort->field);
        $this->assertTrue($sort->isDescending());
        $this->assertSame('-created_at', $sort->toSigned());
    }

    public function test_the_default_is_newest_first(): void
    {
        $this->assertSame('-created_at', ArticleListSort::default()->toSigned());
        $this->assertSame('-created_at', ArticleListSort::fromSignedOrDefault(null)->toSigned());
        $this->assertSame('-created_at', ArticleListSort::fromSignedOrDefault('')->toSigned());
    }

    /**
     * views_total has never been backed by a real column. It must be rejected as
     * input, not turned into SQL.
     */
    #[DataProvider('unsupportedSortProvider')]
    public function test_unsupported_sorts_are_rejected(string $signed): void
    {
        $this->expectException(ValueObjectValidationException::class);

        ArticleListSort::fromSigned($signed);
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
        foreach (ArticleListSort::allowedValues() as $value) {
            $this->assertSame($value, ArticleListSort::fromSigned($value)->toSigned());
        }
    }

    public function test_the_advertised_set_is_the_four_fields_in_both_directions(): void
    {
        $this->assertCount(8, ArticleListSort::allowedValues());
    }

    #[DataProvider('legacySortProvider')]
    public function test_legacy_sort_by_and_dir_normalize_to_a_signed_value(
        ?string $sortBy,
        ?string $sortDir,
        string $expected,
    ): void {
        $this->assertSame($expected, ArticleListSort::fromLegacy($sortBy, $sortDir)->toSigned());
    }

    public static function legacySortProvider(): array
    {
        return [
            'explicit desc' => ['created_at', 'desc', '-created_at'],
            'explicit asc' => ['created_at', 'asc', 'created_at'],
            'title asc' => ['title_jp', 'asc', 'title_jp'],
            'missing direction defaults to desc' => ['updated_at', null, '-updated_at'],
            'missing field defaults to created_at' => [null, 'asc', 'created_at'],
            'both missing' => [null, null, '-created_at'],
            'mixed case direction' => ['created_at', 'ASC', 'created_at'],
        ];
    }
}
