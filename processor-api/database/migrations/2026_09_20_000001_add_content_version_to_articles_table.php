<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #257: every article carries a content version, bumped whenever `title_jp` or
 * `content_jp` changes. Processing jobs are dispatched with the version they were queued for,
 * so a job whose version no longer matches the article marks itself superseded instead of
 * applying an older extraction on top of newer content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->unsignedInteger('content_version')->default(1)->after('uncommon');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn('content_version');
        });
    }
};
