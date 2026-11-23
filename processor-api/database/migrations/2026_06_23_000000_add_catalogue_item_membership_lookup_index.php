<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customlist_object', function (Blueprint $table) {
            $table->index(['list_id', 'real_object_id'], 'catalogue_item_list_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customlist_object', function (Blueprint $table) {
            $table->dropIndex('catalogue_item_list_item_idx');
        });
    }
};
