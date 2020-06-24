<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;

class Sentence extends Model
{
    protected $table = "japanese_tatoeba_sentences";

    public function kanjis() {
        return $this->belongsToMany(Kanji::class, 'japanese_sentence_kanji');
    }

    // public function words() { // Not generated table yet...
    //     return $this->belongsToMany('App\Word', 'japanese_sentence_word');
    // }
}
