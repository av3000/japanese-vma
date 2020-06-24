<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;
use App\Http\Models\Article;

class Word extends Model
{
    protected $table = "japanese_word_bank_long";

    public function kanjis() {
        return $this->belongsToMany(Kanji::class, 'japanese_kanji_word_long');
    }

    public function articles() {
        return $this->belongsToMany(Article::class, 'article_word');
    }

    // public function sentences() { Not yet
    //     return $this->belongsToMany('App\Sentence', 'japanese_sentence_word');
    // }
}
