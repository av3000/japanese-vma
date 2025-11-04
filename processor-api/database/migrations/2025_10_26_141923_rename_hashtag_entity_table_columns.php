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
        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->renameColumn('template_id', 'entity_type_id');
            $table->renameColumn('real_object_id', 'entity_id');
            $table->renameColumn('uniquehashtag_id', 'hashtag_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hashtag_entity', function (Blueprint $table) {
            $table->renameColumn('entity_type_id', 'template_id');
            $table->renameColumn('entity_id', 'real_object_id');
            $table->renameColumn('hashtag_id', 'uniquehashtag_id');
        });
    }
};
