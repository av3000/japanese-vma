<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\StudyAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyAttempt extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected static function newFactory(): StudyAttemptFactory
    {
        return StudyAttemptFactory::new();
    }

    protected $fillable = [
        'session_id',
        'item_id',
        'entity_type_uuid',
        'attempt_no',
        'given_answer',
        'expected_answers',
        'is_correct',
        'response_ms',
        'answered_at',
    ];

    protected $casts = [
        'item_id' => 'integer',
        'attempt_no' => 'integer',
        'expected_answers' => 'array',
        'is_correct' => 'boolean',
        'response_ms' => 'integer',
        'answered_at' => 'immutable_datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(StudySession::class, 'session_id');
    }
}
