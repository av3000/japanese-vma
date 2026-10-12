<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry of a catalogue (`customlist_object`): the numeric id of a radical, kanji, word,
 * sentence or article. What `real_object_id` points at follows from the catalogue's type.
 *
 * @property int $id
 * @property int $list_id
 * @property int $real_object_id
 * @property string $listtype_id
 */
class CatalogueItem extends Model
{
    protected $table = 'customlist_object';

    protected $guarded = [];

    protected $casts = [
        'list_id' => 'integer',
        'real_object_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function catalogue(): BelongsTo
    {
        return $this->belongsTo(Catalogue::class, 'list_id');
    }
}
