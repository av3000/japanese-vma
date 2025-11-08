<?php
namespace App\Infrastructure\Persistence\Models;
use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use App\Infrastructure\Persistence\Models\Uniquehashtag;

class HashtagEntity extends Model
{
    use SoftDeletes;
    // TODO: Create a job for this relationship entity Soft Deleted records to move to different table for analytics only
    // to reduce hashtag_entity read operations loading time.
    // Following phases could be moving to separate DB adjusted for better analytics and reads.
    // and later to Data Lake/Warehouse when data volume makes sense.

    protected $table = 'hashtag_entity';

    protected $fillable = [
        'entity_type_id',
        'entity_id',
        'hashtag_id',
        'user_id',
    ];

    public function uniquehashtag()
    {
        return $this->belongsTo(Uniquehashtag::class, 'hashtag_id');
    }
}
