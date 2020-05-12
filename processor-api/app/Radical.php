<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Radical extends Model
{
    protected $table = "japanese_radicals_bank_long";

    public function kanjis() {
        return $this->belongsToMany('App\Kanji', 'japanese_radical_kanji_long');
    }

    // public function getRouteKeyName()
    // {
    //     return 'radical';
    // } 
}
