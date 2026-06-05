<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sentence extends Model
{
    protected $table = 'japanese_tatoeba_sentences';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'tatoeba_entry',
        'content',
    ];

    protected $casts = [
        'uuid' => 'string',
        'user_id' => 'integer',
        'tatoeba_entry' => 'string',
        'content' => 'string',
    ];

    public function kanjis(): BelongsToMany
    {
        return $this->belongsToMany(Kanji::class, 'japanese_sentence_kanji');
    }
}
