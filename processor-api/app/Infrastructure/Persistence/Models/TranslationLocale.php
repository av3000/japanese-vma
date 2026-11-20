<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\TranslationLocaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A language the interface can be shown in. The `en` row is created by the migration; every
 * other row is added by an administrator and is inactive until switched on.
 */
class TranslationLocale extends Model
{
    use HasFactory;

    protected static function newFactory(): TranslationLocaleFactory
    {
        return TranslationLocaleFactory::new();
    }

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
