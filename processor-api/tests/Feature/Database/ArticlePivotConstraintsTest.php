<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Issue #248: the article pivots carry a uniqueness guarantee and both lookup directions are
 * indexed, and the kanji bank is indexed on the character the attachment service resolves by.
 */
class ArticlePivotConstraintsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function pivots(): array
    {
        return [
            'article_kanji' => ['article_kanji', 'kanji_id'],
            'article_word' => ['article_word', 'word_id'],
        ];
    }

    #[DataProvider('pivots')]
    public function test_duplicate_pair_is_rejected(string $pivot, string $foreignColumn): void
    {
        DB::table($pivot)->insert(['article_id' => 1, $foreignColumn => 7]);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table($pivot)->insert(['article_id' => 1, $foreignColumn => 7]);
    }

    #[DataProvider('pivots')]
    public function test_same_foreign_row_can_attach_to_different_articles(string $pivot, string $foreignColumn): void
    {
        DB::table($pivot)->insert([
            ['article_id' => 1, $foreignColumn => 7],
            ['article_id' => 2, $foreignColumn => 7],
        ]);

        $this->assertSame(2, DB::table($pivot)->where($foreignColumn, 7)->count());
    }

    #[DataProvider('pivots')]
    public function test_pivot_indexes_exist(string $pivot, string $foreignColumn): void
    {
        $this->assertTrue(Schema::hasIndex($pivot, ['article_id', $foreignColumn], 'unique'));
        $this->assertTrue(Schema::hasIndex($pivot, [$foreignColumn]));
    }

    public function test_kanji_bank_is_indexed_on_the_character(): void
    {
        $this->assertTrue(Schema::hasIndex('japanese_kanji_bank_long', ['kanji']));
    }
}
