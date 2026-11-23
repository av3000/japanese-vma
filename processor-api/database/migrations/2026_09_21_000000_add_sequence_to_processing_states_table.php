<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #261, audit F-25: a counter that only ever goes up for a row, so a subscriber can tell
 * a late event from a current one. Timestamps cannot do this — two transitions inside the same
 * second share an `updated_at`, and clocks are not ordering.
 *
 * Existing rows start at 0 and take their first real value on the next transition, which is
 * correct: a client that has never seen an event has nothing to compare against.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('processing_states', function (Blueprint $table): void {
            $table->unsignedInteger('sequence')->default(0)->after('attempt');
        });
    }

    public function down(): void
    {
        Schema::table('processing_states', function (Blueprint $table): void {
            $table->dropColumn('sequence');
        });
    }
};
