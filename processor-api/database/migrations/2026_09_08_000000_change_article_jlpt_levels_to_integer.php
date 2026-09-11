<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The Article JLPT columns were created as VARCHAR defaulting to the string "0",
 * so the approved `n2 > 0` filter semantics cannot execute on PostgreSQL:
 * "operator does not exist: character varying > integer".
 *
 * This follows the precedent already set for japanese_kanji_bank_long.stroke_count
 * in 2026_09_04_000000_change_kanji_stroke_count_to_integer. The equivalent
 * customlists columns are already integers; articles was the outlier.
 */
return new class extends Migration
{
    private const COLUMNS = ['n1', 'n2', 'n3', 'n4', 'n5', 'uncommon'];

    public function up(): void
    {
        foreach (self::COLUMNS as $column) {
            // Empty strings and stray non-numeric values would abort the cast, so
            // normalise them to '0' first rather than failing the deploy.
            DB::statement(
                "UPDATE articles SET {$column} = '0' WHERE {$column} IS NULL OR {$column} !~ '^[0-9]+$'",
            );

            // The existing DEFAULT is the string '0'. PostgreSQL refuses to cast a
            // default automatically, so it has to be dropped and re-added as an integer.
            DB::statement("ALTER TABLE articles ALTER COLUMN {$column} DROP DEFAULT");

            DB::statement(
                "ALTER TABLE articles ALTER COLUMN {$column} TYPE INTEGER USING {$column}::integer",
            );

            DB::statement("ALTER TABLE articles ALTER COLUMN {$column} SET DEFAULT 0");
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            DB::statement("ALTER TABLE articles ALTER COLUMN {$column} DROP DEFAULT");

            DB::statement(
                "ALTER TABLE articles ALTER COLUMN {$column} TYPE VARCHAR(255) USING {$column}::varchar",
            );

            DB::statement("ALTER TABLE articles ALTER COLUMN {$column} SET DEFAULT '0'");
        }
    }
};
