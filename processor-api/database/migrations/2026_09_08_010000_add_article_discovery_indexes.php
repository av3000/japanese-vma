<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Indexes for the Article discovery access paths introduced by AFM-03.
 *
 * `articles` carried no index on publicity, created_at or user_id, so every list
 * request was a sequential scan plus a top-N sort. Each index below is justified by
 * an EXPLAIN plan recorded on a 20k-row dataset; see the AFM-04 pull request.
 *
 * Deliberately NOT added here: article_kanji and article_word reverse indexes.
 * av3000/japanese-vma#248 owns those, and duplicating them would create competing
 * migrations.
 *
 * Also deliberately not added: per-sort indexes for updated_at, title_jp and
 * title_en. They are accepted sort values but no evidence yet shows real traffic
 * using them, and each one costs write throughput on every Article insert.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Default discovery path: public Articles ordered by created_at with the id
        // tie-breaker. A btree is scannable in both directions, so one index serves
        // both `-created_at` and `created_at`.
        DB::statement(
            'CREATE INDEX articles_publicity_created_at_id_index
             ON articles (publicity, created_at, id)',
        );

        // The owned-private branch of the visibility predicate, and any lookup that
        // narrows to one author before ordering.
        DB::statement(
            'CREATE INDEX articles_user_id_publicity_created_at_id_index
             ON articles (user_id, publicity, created_at, id)',
        );

        // Reverse hashtag lookup. hashtag_entity already has (entity_type_id, entity_id)
        // and (hashtag_id), but neither serves "which Articles carry this tag" without
        // re-checking every candidate row. Partial on deleted_at because the filter
        // never wants soft-deleted links, which keeps the index smaller than the table.
        DB::statement(
            'CREATE INDEX hashtag_entity_article_reverse_index
             ON hashtag_entity (entity_type_id, hashtag_id, entity_id)
             WHERE deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS articles_publicity_created_at_id_index');
        DB::statement('DROP INDEX IF EXISTS articles_user_id_publicity_created_at_id_index');
        DB::statement('DROP INDEX IF EXISTS hashtag_entity_article_reverse_index');
    }
};
