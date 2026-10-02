<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #407: one row per Import Run, so a scraper that quietly stops working (an unofficial API
 * changing shape, say) shows up as failed runs or as runs that create nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_import_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('content_source_id')->constrained('content_sources')->cascadeOnDelete();
            $table->string('status', 16);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('listed')->default(0);
            $table->unsignedInteger('created')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['content_source_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_import_runs');
    }
};
