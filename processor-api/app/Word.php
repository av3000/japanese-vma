<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    protected $table = "japanese_word_bank_long";

    public function kanjis() {
        return $this->belongsToMany('App\Kanji', 'japanese_kanji_word_long');
    }

    public function articles() {
        return $this->belongsToMany('App\Article', 'article_word');
    }
}
