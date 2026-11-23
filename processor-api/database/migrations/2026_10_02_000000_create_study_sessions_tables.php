<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Epic #413: what a signed-in learner did with a flashcard deck. A session is one run with
 * one configuration; an attempt is one answer. The verdict is the client's (grading is
 * client-side by decision), so `expected_answers` is stored beside it for a later re-grade.
 *
 * `catalogue_id` is set null when the catalogue is deleted: history outlives its source,
 * and `catalogue_type` is snapshotted for the same reason. `customlists.id` is a 32-bit
 * `increments` column, so the referencing column matches it rather than using `foreignId`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('catalogue_id')->nullable();
            $table->unsignedSmallInteger('catalogue_type');
            $table->string('prompt_field', 16);
            $table->string('answer_field', 16);
            $table->string('answer_mode', 16);
            $table->string('script_strictness', 16);
            $table->unsignedSmallInteger('card_count');
            $table->unsignedSmallInteger('correct_count')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('catalogue_id')->references('id')->on('customlists')->nullOnDelete();
            $table->index(['user_id', 'completed_at'], 'study_sessions_user_completed_index');
            $table->index('catalogue_id', 'study_sessions_catalogue_index');
        });

        Schema::create('study_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')->constrained('study_sessions')->cascadeOnDelete();
            $table->unsignedInteger('item_id');
            $table->uuid('entity_type_uuid');
            $table->unsignedSmallInteger('attempt_no')->default(1);
            $table->text('given_answer')->nullable();
            $table->jsonb('expected_answers');
            $table->boolean('is_correct');
            $table->unsignedInteger('response_ms')->nullable();
            $table->timestamp('answered_at');

            $table->unique(['session_id', 'item_id', 'attempt_no'], 'study_attempts_session_item_attempt_unique');
            // Phase 2 ("how often is this kanji missed") reads by item across sessions.
            $table->index(['entity_type_uuid', 'item_id'], 'study_attempts_item_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_attempts');
        Schema::dropIfExists('study_sessions');
    }
};
