<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('japanese_sentence_word', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('sentence_id');
            $table->unsignedInteger('word_id');
            // The unique index already serves sentence_id lookups.
            $table->unique(['sentence_id', 'word_id']);
            $table->index('word_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('japanese_sentence_word');
    }
};
