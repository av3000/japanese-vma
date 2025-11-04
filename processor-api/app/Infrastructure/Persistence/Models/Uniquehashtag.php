<?php
namespace App\Infrastructure\Persistence\Models;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Models\HashtagEntity;

class Uniquehashtag extends Model
{
    protected $table = "uniquehashtags";

    protected $fillable = ['content'];

    public function hashtagEntities()
    {
        return $this->hasMany(HashtagEntity::class, 'hashtag_id');
    }
}
