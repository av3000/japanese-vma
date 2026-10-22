<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\ContentImport\Enums\ImportRunStatus;
use Illuminate\Database\Eloquent\Model;

/**
 * One Import Run against one Content Source (issue #407).
 *
 * @property int $id
 * @property int $content_source_id
 * @property ImportRunStatus $status
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property int $listed
 * @property int $created
 * @property int $skipped
 * @property int $failed
 * @property string|null $error
 */
class ContentImportRun extends Model
{
    protected $table = 'content_import_runs';

    protected $guarded = [];

    protected $casts = [
        'status' => ImportRunStatus::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'listed' => 'integer',
        'created' => 'integer',
        'skipped' => 'integer',
        'failed' => 'integer',
    ];
}
