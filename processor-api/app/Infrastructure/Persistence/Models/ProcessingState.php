<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Processing\Enums\ProcessingEntityType;
use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * One current-state row per (entity_type, entity_id, task_type). ADR 0001.
 *
 * @property int $id
 * @property ProcessingEntityType $entity_type
 * @property string $entity_id
 * @property string $task_type
 * @property LastOperationStatus $status
 * @property int $attempt
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
        'status' => LastOperationStatus::class,
        'attempt' => 'integer',
        'max_attempts' => 'integer',
        'content_version' => 'integer',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
