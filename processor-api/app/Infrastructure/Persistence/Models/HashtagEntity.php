<?php
namespace App\Infrastructure\Persistence\Models;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Models\Uniquehashtag;

class HashtagEntity extends Model
{
    protected $table = 'hashtag_entity';

    protected $fillable = [
        'entity_type_id',
        'entity_id',
        'hashtag_id',
        'user_id',
        'name',
    ];

    public function uniquehashtag()
    {
        return $this->belongsTo(Uniquehashtag::class, 'hashtag_id');
    }
}
