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
        Schema::create('last_operations', function (Blueprint $table) {
            $table->id();

            // Creates 'processable_id' (uuid) and 'processable_type' (string)
            $table->uuidMorphs('processable');

            $table->string('task_type')->index();

            $table->string('status')->default('pending')->index();

            $table->json('metadata')->nullable();

            $table->timestamps();

            // Optimization: Index for looking up a specific task for an entity
            $table->index(['processable_id', 'processable_type', 'task_type'], 'last_ops_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('last_operations');
    }
};
