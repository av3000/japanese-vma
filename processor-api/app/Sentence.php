<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sentence extends Model
{
    protected $table = "japanese_tatoeba_sentences";

    public function kanjis() {
        return $this->belongsToMany('App\Kanji', 'japanese_sentence_kanji');
    }

    // public function words() { // Not generated table yet...
    //     return $this->belongsToMany('App\Word', 'japanese_sentence_word');
    // }
}
