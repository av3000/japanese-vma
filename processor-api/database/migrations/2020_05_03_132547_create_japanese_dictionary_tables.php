<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('japanese_kanji_bank_long')) {
            Schema::create('japanese_kanji_bank_long', function (Blueprint $table) {
                $table->increments('id');
                $table->string('kanji', 255);
                $table->string('onyomi', 255);
                $table->string('kunyomi', 255);
                $table->string('meaning', 255);
                $table->string('nanori', 255);
                $table->string('grade', 255);
                $table->string('stroke_count', 5);
                $table->string('jlpt', 5);
                $table->string('frequency', 5);
                $table->string('radicals', 255);
                $table->string('radical_parts', 30);
            });
        }

        if (!Schema::hasTable('japanese_radicals_bank_long')) {
            Schema::create('japanese_radicals_bank_long', function (Blueprint $table) {
                $table->increments('id');
                $table->string('radical', 13)->nullable();
                $table->integer('strokes')->nullable();
                $table->string('meaning', 17)->nullable();
                $table->string('hiragana', 22)->nullable();
            });
        }

        if (!Schema::hasTable('japanese_word_bank')) {
            Schema::create('japanese_word_bank', function (Blueprint $table) {
                $table->increments('id');
                $table->string('word', 255);
                $table->string('furigana', 255);
                $table->string('jlpt', 10);
                $table->string('meaning', 255);
                $table->string('word_type', 255);
            });
        }

        if (!Schema::hasTable('japanese_word_bank_long')) {
            Schema::create('japanese_word_bank_long', function (Blueprint $table) {
                $table->increments('id');
                $table->string('entry_sequence', 255);
                $table->string('word', 255);
                $table->string('furigana', 255);
                $table->string('jlpt', 255);
                $table->string('word_type', 255);
                $table->text('word_k_ele');
                $table->text('furigana_r_ele');
                $table->text('sense');
            });
        }

        if (!Schema::hasTable('japanese_tatoeba_sentences')) {
            Schema::create('japanese_tatoeba_sentences', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->nullable();
                $table->string('tatoeba_entry', 255)->nullable();
                $table->text('content');
            });
        }

        if (!Schema::hasTable('japanese_radical_kanji_long')) {
            Schema::create('japanese_radical_kanji_long', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('radical_id');
                $table->integer('kanji_id');
                $table->index('radical_id');
                $table->index('kanji_id');
            });
        }

        if (!Schema::hasTable('japanese_kanji_word_long')) {
            Schema::create('japanese_kanji_word_long', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('kanji_id');
                $table->integer('word_id');
                $table->index('kanji_id');
                $table->index('word_id');
            });
        }

        if (!Schema::hasTable('japanese_sentence_kanji')) {
            Schema::create('japanese_sentence_kanji', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sentence_id');
                $table->integer('kanji_id');
                $table->index('sentence_id');
                $table->index('kanji_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('japanese_sentence_kanji');
        Schema::dropIfExists('japanese_kanji_word_long');
        Schema::dropIfExists('japanese_radical_kanji_long');
        Schema::dropIfExists('japanese_tatoeba_sentences');
        Schema::dropIfExists('japanese_word_bank_long');
        Schema::dropIfExists('japanese_word_bank');
        Schema::dropIfExists('japanese_radicals_bank_long');
        Schema::dropIfExists('japanese_kanji_bank_long');
    }
};
