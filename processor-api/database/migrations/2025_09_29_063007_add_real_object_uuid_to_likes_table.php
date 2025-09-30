<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->uuid('real_object_uuid')->nullable()->after('entity_type_uuid');
            // TODO: add unique constraint for user_id, template_id, real_object_id per like
            // $table->unique(['user_id', 'template_id', 'real_object_id'], 'unique_user_like');
            $table->index('real_object_uuid');
            $table->index(['entity_type_uuid', 'real_object_uuid']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex(['real_object_uuid']);
            $table->dropIndex(['entity_type_uuid', 'real_object_uuid']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('real_object_uuid');
        });
    }
};
