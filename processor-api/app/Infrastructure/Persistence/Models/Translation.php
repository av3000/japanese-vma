<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Database\Factories\TranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stored non-English value for one key in one locale. English is never stored here.
 */
class Translation extends Model
{
    use HasFactory;

    protected static function newFactory(): TranslationFactory
    {
        return TranslationFactory::new();
    }

    protected $fillable = [
        'translation_locale_id',
        'key',
        'value',
        'updated_by_user_id',
    ];

    protected $casts = [
        'translation_locale_id' => 'integer',
        'updated_by_user_id' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /** @return BelongsTo<TranslationLocale, $this> */
    public function locale(): BelongsTo
    {
        return $this->belongsTo(TranslationLocale::class, 'translation_locale_id');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
