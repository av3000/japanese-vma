<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Radical extends Model
{
    protected $table = 'japanese_radicals_bank_long';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'radical',
        'strokes',
        'meaning',
        'hiragana',
    ];

    protected $casts = [
        'uuid' => 'string',
        'radical' => 'string',
        'strokes' => 'integer',
        'meaning' => 'string',
        'hiragana' => 'string',
    ];

    public function kanjis(): BelongsToMany
    {
        return $this->belongsToMany(Kanji::class, 'japanese_radical_kanji_long');
    }
}
