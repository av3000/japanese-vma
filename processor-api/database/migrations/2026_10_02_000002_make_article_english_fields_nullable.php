<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #406: the domain Article and `content_en` validation have always treated the English
 * fields as optional, but the 2020 columns were NOT NULL with an empty-string default, so an
 * explicit null (an article with no English content, and every Imported Article) failed to
 * insert. Existing empty strings are left as they are.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('title_en')->nullable()->default(null)->change();
            $table->text('content_en')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        DB::table('articles')->whereNull('title_en')->update(['title_en' => '']);
        DB::table('articles')->whereNull('content_en')->update(['content_en' => '']);

        Schema::table('articles', function (Blueprint $table): void {
            $table->string('title_en')->nullable(false)->default('')->change();
            $table->text('content_en')->nullable(false)->default('')->change();
        });
    }
};
