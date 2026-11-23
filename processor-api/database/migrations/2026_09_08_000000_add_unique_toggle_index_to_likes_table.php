<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The generic like toggle has no uniqueness guarantee: `likes` was created without
 * indexes in 2020 and the remaining ones were dropped in
 * 2025_10_10_084556_remove_uuid_from_likes_table. Two concurrent toggles for the same
 * (user, target) can both miss the existence check and both insert.
 *
 * The unique index is what makes the toggle safe; the transaction in ToggleLikeAction
 * only serialises around it. The composite index serves the likes_count query that
 * every toggle response carries.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pre-existing duplicates would fail the unique index. Keep the oldest row of
        // each tuple - a like has no state beyond its existence, so which survivor we
        // keep only affects `created_at`.
        DB::statement(<<<'SQL'
            DELETE FROM likes a
            USING likes b
            WHERE a.id > b.id
              AND a.user_id = b.user_id
              AND a.template_id = b.template_id
              AND a.real_object_id = b.real_object_id
        SQL);

        Schema::table('likes', function (Blueprint $table): void {
            $table->unique(['user_id', 'template_id', 'real_object_id'], 'likes_user_target_unique');
            $table->index(['template_id', 'real_object_id'], 'likes_target_index');
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table): void {
            $table->dropUnique('likes_user_target_unique');
            $table->dropIndex('likes_target_index');
        });
    }
};
