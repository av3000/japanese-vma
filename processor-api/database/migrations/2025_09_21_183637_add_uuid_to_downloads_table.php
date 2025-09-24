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
            $table->string('real_object_uuid')->nullable()->after('real_object_id');
            $table->string('object_template_uuid')->nullable()->after('template_id');

            $table->index(['object_template_uuid', 'real_object_uuid'], 'likes_uuid_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex('likes_uuid_lookup');
            $table->dropColumn(['real_object_uuid', 'object_template_uuid']);
        });
    }
};
