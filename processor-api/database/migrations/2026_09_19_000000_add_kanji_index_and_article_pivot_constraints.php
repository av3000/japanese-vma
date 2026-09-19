<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #248 (audit findings F-19 and part of F-03).
 *
 * - `japanese_kanji_bank_long.kanji` had no index, so every kanji attachment resolved its
 *   characters with a sequential scan over the whole kanji bank. Lookups are equality
 *   (`WHERE kanji IN (...)`), so a plain btree is enough on both Postgres and MySQL.
 * - `article_kanji` and `article_word` had only an auto-increment id: no uniqueness, and no
 *   index on either side. Every read and sync filters by `article_id`, and the reverse
 *   lookups (kanji -> articles, word -> articles) filter by the other column. The unique
 *   constraint on `(article_id, kanji_id)` / `(article_id, word_id)` doubles as the
 *   `article_id` index; the reverse index covers the other direction.
 * - Duplicate pairs are removed before the constraints are added, keeping the lowest id.
 *
 * `japanese_word_bank_long.word` is deliberately not touched: migration
 * 2026_09_02_000000_add_word_index_to_japanese_word_bank_long_table already adds both the
 * plain index and the Postgres `varchar_pattern_ops` index for prefix LIKE.
 */
return new class extends Migration
{
    private const PIVOTS = [
        'article_kanji' => 'kanji_id',
        'article_word' => 'word_id',
    ];

    public function up(): void
    {
        Schema::table('japanese_kanji_bank_long', function (Blueprint $table): void {
            $table->index('kanji');
        });

        foreach (self::PIVOTS as $pivot => $foreignColumn) {
            $this->deleteDuplicatePairs($pivot, $foreignColumn);

            Schema::table($pivot, function (Blueprint $table) use ($foreignColumn): void {
                $table->unique(['article_id', $foreignColumn]);
                $table->index($foreignColumn);
            });
        }
    }

    public function down(): void
    {
        foreach (self::PIVOTS as $pivot => $foreignColumn) {
            Schema::table($pivot, function (Blueprint $table) use ($foreignColumn): void {
                $table->dropIndex([$foreignColumn]);
                $table->dropUnique(['article_id', $foreignColumn]);
            });
        }

        Schema::table('japanese_kanji_bank_long', function (Blueprint $table): void {
            $table->dropIndex(['kanji']);
        });
    }

    /**
     * Keep the lowest id per (article_id, foreign) pair. The extra derived-table wrapper is
     * what lets MySQL delete from a table it is also selecting from; Postgres and SQLite
     * accept it too.
     */
    private function deleteDuplicatePairs(string $pivot, string $foreignColumn): void
    {
        DB::statement(<<<SQL
            DELETE FROM {$pivot}
            WHERE id NOT IN (
                SELECT id FROM (
                    SELECT MIN(id) AS id
                    FROM {$pivot}
                    GROUP BY article_id, {$foreignColumn}
                ) AS keep
            )
        SQL);
    }
};
