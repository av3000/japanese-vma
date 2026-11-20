<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Epic #659: the languages the interface can be shown in. English is the code-owned source and
 * the fallback, so its row is created here, in the migration: every environment, including a
 * `RefreshDatabase` test, has it without seeding. Only non-English codes are ever added later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_locales', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        DB::table('translation_locales')->insert([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_locales');
    }
};
