<?php
namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\ObjectTemplate;
use App\Http\User;

/**
 * View Model
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $user_ip
 * @property int $template_id
 * @property int $real_object_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class View extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'user_ip',
        'template_id',
        'real_object_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
        'template_id' => 'integer',
        'real_object_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the object template that owns the view.
     */
    public function objecttemplate()
    {
        return $this->belongsTo(ObjectTemplate::class, 'template_id');
    }

    /**
     * Get the user that owns the view.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
