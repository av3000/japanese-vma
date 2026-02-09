<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\LastOperationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LastOperationState extends Model
{
    protected $table = 'last_operations';

    protected $guarded = [];

    protected $casts = [
        'status' => LastOperationStatus::class,
        'metadata' => 'array',
    ];

    public function processable(): MorphTo
    {
        return $this->morphTo();
    }
}
