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
        Schema::table('comments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('japanese_radicals_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('japanese_kanji_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('japanese_word_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('japanese_tatoeba_sentences', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->uuid('entity_type_uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('japanese_radicals_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('japanese_kanji_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('japanese_word_bank_long', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('japanese_tatoeba_sentences', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });

        Schema::table('objecttemplates', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->change();
        });
    }
};
