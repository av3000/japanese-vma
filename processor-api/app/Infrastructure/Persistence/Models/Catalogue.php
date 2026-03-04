<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\CatalogueType;
use Illuminate\Database\Eloquent\Model;
use App\Http\User;

class Catalogue extends Model
{
    protected $table = "customlists";

    protected $fillable = [
        'title',
        'description',
        'publicity',
        'type',
        'user_id',
        'uuid',
    ];

    protected $casts = [
        'publicity' => 'boolean',
        'type' => CatalogueType::class,
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
