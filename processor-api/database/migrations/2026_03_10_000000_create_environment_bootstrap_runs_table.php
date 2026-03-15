<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_bootstrap_runs', function (Blueprint $table) {
            $table->id();
            $table->string('environment', 100);
            $table->string('task', 100);
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['environment', 'task']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_bootstrap_runs');
    }
};
