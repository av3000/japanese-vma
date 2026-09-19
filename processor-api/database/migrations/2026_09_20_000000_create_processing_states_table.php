<?php

declare(strict_types=1);

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingTaskType;
use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 0001, issue #256: one current-state row per (entity_type, entity_id, task_type),
 * updated in place, replacing the append-only `last_operations` history.
 *
 * Backfills from `last_operations`: for each article the newest row across both legacy task
 * types becomes the single `article_content_processing` row. `last_operations` is kept for one
 * release so a rollback still has its data; a follow-up migration drops it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('processing_states', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 32);
            $table->uuid('entity_id');
            $table->string('task_type', 64);
            $table->string('status', 16)->default(LastOperationStatus::PENDING->value);
            $table->unsignedSmallInteger('attempt')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(3);
            $table->unsignedInteger('content_version')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 300)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'task_type'], 'processing_states_entity_task_unique');
            // The stale sweeper filters non-terminal rows by age; the batch read filters by task.
            $table->index(['status', 'updated_at'], 'processing_states_status_updated_at_index');
            $table->index(['task_type', 'entity_id'], 'processing_states_task_entity_index');
        });

        if (Schema::hasTable('last_operations')) {
            $this->backfillFromLastOperations();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('processing_states');
    }

    private function backfillFromLastOperations(): void
    {
        $legacyTaskTypes = array_map(
            static fn (ProcessingTaskType $type): string => $type->value,
            ProcessingTaskType::legacy(),
        );

        // Newest row per article across both legacy tasks: id is monotonic, so MAX(id) is it.
        $latestIds = DB::table('last_operations')
            ->selectRaw('MAX(id) AS id')
            ->whereIn('task_type', $legacyTaskTypes)
            ->groupBy('processable_id')
            ->pluck('id');

        foreach ($latestIds->chunk(500) as $idChunk) {
            $rows = DB::table('last_operations')->whereIn('id', $idChunk->all())->get();

            $inserts = $rows->map(function (object $row): array {
                $metadata = is_string($row->metadata) ? json_decode($row->metadata, true) : null;
                $metadata = is_array($metadata) ? $metadata : [];
                $status = LastOperationStatus::tryFrom((string) $row->status) ?? LastOperationStatus::FAILED;

                return [
                    'entity_type' => ProcessingEntityType::Article->value,
                    'entity_id' => $row->processable_id,
                    'task_type' => ProcessingTaskType::ArticleContentProcessing->value,
                    'status' => $status->value,
                    'attempt' => max(0, (int) ($metadata['attempts'] ?? 1)),
                    'max_attempts' => 3,
                    'content_version' => 1,
                    'started_at' => $row->created_at,
                    'finished_at' => $status->isTerminal() ? $row->updated_at : null,
                    'error_code' => $status === LastOperationStatus::FAILED ? 'legacy' : null,
                    'error_message' => isset($metadata['error']) ? mb_substr((string) $metadata['error'], 0, 300) : null,
                    'metadata' => json_encode(
                        array_intersect_key($metadata, array_flip(['kanji_count', 'word_count'])),
                        JSON_THROW_ON_ERROR,
                    ),
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            })->all();

            if ($inserts !== []) {
                DB::table('processing_states')->insert($inserts);
            }
        }
    }
};
