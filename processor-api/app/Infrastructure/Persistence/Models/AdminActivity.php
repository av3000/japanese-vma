<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $actor_id
 * @property string $actor_name
 * @property ActivityAction $action
 * @property ActivitySubjectType $subject_type
 * @property string $subject_id
 * @property string|null $subject_label
 * @property array{before?: array<string, mixed>, after?: array<string, mixed>}|null $changes
 * @property ActivitySource $source
 * @property array<string, mixed>|null $context
 * @property \Illuminate\Support\Carbon $created_at
 */
class AdminActivity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'admin_activities';

    protected $guarded = [];

    protected $casts = [
        'action' => ActivityAction::class,
        'subject_type' => ActivitySubjectType::class,
        'source' => ActivitySource::class,
        'changes' => 'array',
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
