<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #405: the registry of external publishers the Content Import context reads from. The
 * `key` is how code names a source (and how its adapter is looked up); `enabled` is the
 * per-source kill switch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('name');
            $table->string('homepage_url', 500);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_sources');
    }
};
