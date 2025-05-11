<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\User;

/**
 * CustomList Model
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property bool $publicity
 * @property int $type
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read \App\Http\User $user
 */
class CustomList extends Model
{
    protected $table = "customlists";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'publicity',
        'type',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'publicity' => 'boolean',
        'type' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should have default values.
     *
     * @var array
     */
    protected $attributes = [
        'publicity' => 0,
        'type' => 5,
    ];

    /**
     * Get the user that owns the list.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
