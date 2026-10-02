<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #405: where an article came from. Tags are user-editable and soft-deletable, so they
 * cannot carry provenance; these columns can. The unique pair is what makes importing the same
 * external article twice impossible, even when two runs race.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('origin', 16)->default('user')->after('status');
            $table->foreignId('content_source_id')
                ->nullable()
                ->after('origin')
                ->constrained('content_sources')
                ->nullOnDelete();
            $table->string('external_id', 191)->nullable()->after('content_source_id');

            $table->unique(['content_source_id', 'external_id'], 'articles_content_source_external_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropUnique('articles_content_source_external_id_unique');
            $table->dropConstrainedForeignId('content_source_id');
            $table->dropColumn(['origin', 'external_id']);
        });
    }
};
