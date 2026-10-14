<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An external publisher the Content Import context reads from (issue #405).
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string $homepage_url
 * @property bool $enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ContentSource extends Model
{
    protected $table = 'content_sources';

    protected $fillable = [
        'key',
        'name',
        'homepage_url',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];
}
