<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\SavedListType;
use App\Http\User;
use Database\Factories\CatalogueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// TODO: Should list all model fields without a nee
class Catalogue extends Model
{
    use HasFactory;

    protected static function newFactory(): CatalogueFactory
    {
        return CatalogueFactory::new();
    }

    protected $table = 'customlists';

    protected $fillable = [
        'title',
        'description',
        'publicity',
        'type',
        'user_id',
        'uuid',
        'entity_type_uuid',
    ];

    protected $casts = [
        'publicity' => 'boolean',
        'type' => SavedListType::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    protected $attributes = [
        'publicity' => false,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
