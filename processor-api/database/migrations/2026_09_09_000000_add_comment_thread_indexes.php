<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `comments` was created in 2020 with no indexes; the 2025 migrations only added
 * `uuid` and `entity_type_uuid`. Every thread read filters on
 * (template_id, real_object_id) and the reply walk filters on parent_comment_id,
 * so both were sequential scans.
 *
 * The reply walk is the reason this cannot wait: it recurses over
 * parent_comment_id one level at a time, and an unindexed column turns a bounded
 * traversal into a scan per level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['template_id', 'real_object_id'], 'comments_entity_index');
            $table->index('parent_comment_id', 'comments_parent_comment_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_entity_index');
            $table->dropIndex('comments_parent_comment_id_index');
        });
    }
};
