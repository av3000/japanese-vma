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
       // Drop any existing foreign keys first (in case of partial failure)
        try {
            Schema::table('hashtag_entity', function (Blueprint $table) {
                $table->dropForeign(['hashtag_id']);
                $table->dropForeign(['entity_type_id']);
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Ignore if they don't exist
        }

        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->change();
        });

        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->foreign('hashtag_id')->references('id')->on('uniquehashtags')->onDelete('cascade');
            $table->foreign('entity_type_id')->references('id')->on('objecttemplates')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->index(['entity_type_id', 'entity_id']);
            $table->index(['hashtag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->dropForeign(['hashtag_id']);
            $table->dropForeign(['entity_type_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['entity_type_id', 'entity_id']);
            $table->dropIndex(['hashtag_id']);
            $table->unsignedInteger('user_id')->change();
        });
    }
};
