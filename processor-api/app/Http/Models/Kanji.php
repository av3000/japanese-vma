<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Kanji extends Model
{
    protected $table = "japanese_kanji_bank_long";

    public function words() {
        return $this->belongsToMany(Word::class, 'japanese_kanji_word_long');
    }

    public function sentences() {
        return $this->belongsToMany(Sentence::class, 'japanese_sentence_kanji');
    }

    public function articles() {
        return $this->belongsToMany(Article::class, 'article_kanji');
    }

    public function radicals() {
        return $this->belongsToMany(Radical::class, 'japanese_radical_kanji_long');
    }

    // /**
    //  * Get the route key for the model.
    //  *
    //  * @return string
    //  */
    // public function getRouteKeyName()
    // {
    //     return 'kanji';
    // }
}
