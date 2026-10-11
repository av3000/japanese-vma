<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use App\Domain\Shared\Enums\SavedListType;
use App\Infrastructure\Persistence\Models\Concerns\RoutesByUuid;
use Database\Factories\CatalogueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// TODO: Should list all model fields without a nee
class Catalogue extends Model
{
    use HasFactory;
    use RoutesByUuid;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogueItem::class, 'list_id');
    }
}
