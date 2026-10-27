<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\ContentImport\Enums\SourceTagKind;
use Illuminate\Database\Eloquent\Model;

/**
 * One allow-listed source label and the hashtag it becomes (issue #409).
 *
 * @property int $id
 * @property int $content_source_id
 * @property SourceTagKind $kind
 * @property string $external_key
 * @property string $hashtag
 */
class SourceTagMapping extends Model
{
    protected $table = 'source_tag_mappings';

    protected $fillable = [
        'content_source_id',
        'kind',
        'external_key',
        'hashtag',
    ];

    protected $casts = [
        'kind' => SourceTagKind::class,
    ];
}
