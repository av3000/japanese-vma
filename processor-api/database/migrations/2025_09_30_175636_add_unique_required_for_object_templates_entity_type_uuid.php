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
        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->uuid('entity_type_uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->dropUnique(['entity_type_uuid']);
            $table->uuid('entity_type_uuid')->nullable()->change();
        });
    }
};
