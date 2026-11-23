<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Processing\Enums\ProcessingStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One current-state row per (entity_type, entity_id, task_type). ADR 0001.
 *
 * @property int $id
 * @property ProcessingEntityType $entity_type
 * @property string $entity_id
 * @property string $task_type
 * @property ProcessingStatus $status
 * @property int $attempt
 * @property int $sequence
 * @property int $max_attempts
 * @property int $content_version
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ProcessingState extends Model
{
    protected $table = 'processing_states';

    protected $guarded = [];

    protected $casts = [
        'entity_type' => ProcessingEntityType::class,
        'status' => ProcessingStatus::class,
        'attempt' => 'integer',
        'sequence' => 'integer',
        'max_attempts' => 'integer',
        'content_version' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Non-terminal rows whose last progress is at or before $cutoff: ProcessingThresholds::isStale
     * in SQL (last progress is updated_at, falling back to created_at; neither counts as stale).
     * The updated_at comparison stays sargable, so processing_states_status_updated_at_index
     * still serves it. Pass ProcessingThresholds::staleBefore($now) for the standard threshold.
     */
    public function scopeStale(Builder $query, DateTimeInterface $cutoff): Builder
    {
        return $query
            ->whereIn('processing_states.status', ProcessingStatus::nonTerminalValues())
            ->where(static fn (Builder $progress): Builder => $progress
                ->where('processing_states.updated_at', '<=', $cutoff)
                ->orWhere(static fn (Builder $noUpdate): Builder => $noUpdate
                    ->whereNull('processing_states.updated_at')
                    ->where(static fn (Builder $created): Builder => $created
                        ->whereNull('processing_states.created_at')
                        ->orWhere('processing_states.created_at', '<=', $cutoff))));
    }
}
