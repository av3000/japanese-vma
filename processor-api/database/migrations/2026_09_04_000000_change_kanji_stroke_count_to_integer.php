<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'ALTER TABLE japanese_kanji_bank_long ALTER COLUMN stroke_count TYPE INTEGER USING stroke_count::integer',
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE japanese_kanji_bank_long ALTER COLUMN stroke_count TYPE VARCHAR(5) USING stroke_count::varchar',
        );
    }
};
