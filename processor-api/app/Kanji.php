<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Kanji extends Model
{
    protected $table = "japanese_kanji_bank_long";

    public function words() {
        return $this->belongsToMany('App\Word', 'japanese_kanji_word_long');
    }

    public function articles() {
        return $this->belongsToMany('App\Article', 'article_kanji');
    }

    public function radicals() {
        return $this->belongsToMany('App\Radical', 'japanese_radical_kanji_long');
    }
}
