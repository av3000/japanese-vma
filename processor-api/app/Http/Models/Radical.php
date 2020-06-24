<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;

class Radical extends Model
{
    protected $table = "japanese_radicals_bank_long";

    public function kanjis() {
        return $this->belongsToMany(Kanji::class, 'japanese_radical_kanji_long');
    }

    // public function getRouteKeyName()
    // {
    //     return 'radical';
    // } 
}
