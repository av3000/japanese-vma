<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Word extends Model
{
    protected $table = 'japanese_word_bank_long';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'entry_sequence',
        'word',
        'furigana',
        'jlpt',
        'word_type',
        'word_k_ele',
        'furigana_r_ele',
        'sense',
    ];

    protected $casts = [
        'word' => 'string',
        'furigana' => 'string',
        'jlpt' => 'string',
        'word_type' => 'string',
        'word_k_ele' => 'string',
        'furigana_r_ele' => 'string',
        'sense' => 'string',
    ];

    public function kanjis(): BelongsToMany
    {
        return $this->belongsToMany(Kanji::class, 'japanese_kanji_word_long');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_word');
    }
}
