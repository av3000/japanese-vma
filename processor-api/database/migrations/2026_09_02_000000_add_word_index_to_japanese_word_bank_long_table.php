<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Word extraction resolves a sentence into dictionary entries by probing the
 * word bank once per character position, with an exact match and a prefix
 * match per probe. Without an index on `word` every probe is a sequential scan
 * over the dictionary.
 *
 * The plain index serves the exact lookups. PostgreSQL additionally needs a
 * `varchar_pattern_ops` index for `LIKE 'prefix%'` to be index-backed under a
 * non-C collation.
 */
return new class extends Migration
{
    private const PATTERN_INDEX = 'japanese_word_bank_long_word_pattern_index';

    public function up(): void
    {
        Schema::table('japanese_word_bank_long', function (Blueprint $table): void {
            $table->index('word');
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE INDEX '.self::PATTERN_INDEX.
                ' ON japanese_word_bank_long (word varchar_pattern_ops)'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS '.self::PATTERN_INDEX);
        }

        Schema::table('japanese_word_bank_long', function (Blueprint $table): void {
            $table->dropIndex(['word']);
        });
    }
};
