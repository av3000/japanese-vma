<?php
namespace App\Infrastructure\Persistence\Models;

use App\Http\Models\ObjectTemplate;
use App\Http\User;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
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

    public function objecttemplate()
    {
        return $this->belongsTo(ObjectTemplate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
