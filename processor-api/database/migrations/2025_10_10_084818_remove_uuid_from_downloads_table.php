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
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex(['real_object_uuid']);
            $table->dropIndex(['entity_type_uuid', 'real_object_uuid']);
            $table->dropIndex(['user_id']);
            $table->dropColumn('entity_type_uuid');
            $table->dropColumn('real_object_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->uuid('entity_type_uuid')->nullable()->after('id');
            $table->uuid('real_object_uuid')->nullable()->after('entity_type_uuid');
            $table->index('real_object_uuid');
            $table->index(['entity_type_uuid', 'real_object_uuid']);
            $table->index('user_id');
        });
    }
};
