<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\StudySessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    use HasFactory;

    protected static function newFactory(): StudySessionFactory
    {
        return StudySessionFactory::new();
    }

    protected $fillable = [
        'uuid',
        'user_id',
        'catalogue_id',
        'catalogue_type',
        'prompt_field',
        'answer_field',
        'answer_mode',
        'script_strictness',
        'card_count',
        'correct_count',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'catalogue_type' => 'integer',
        'card_count' => 'integer',
        'correct_count' => 'integer',
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(Catalogue::class, 'catalogue_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StudyAttempt::class, 'session_id');
    }
}
