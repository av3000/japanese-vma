<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Epic #659: stored non-English values (overrides), one per (locale, key). English is code-owned
 * and never stored here. A row has no state of its own: Translated and Missing are derived from
 * whether a non-empty value exists.
 *
 * Deleting a locale removes its rows (there is no delete in the UI yet; deactivating keeps them).
 * Deleting a user keeps the rows and clears who last edited them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_locale_id')->constrained('translation_locales')->cascadeOnDelete();
            $table->string('key', 191);
            $table->text('value');
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['translation_locale_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
