<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Kanji;
use App\Http\Models\Word;
use App\Http\User;

class Article extends Model
{
    protected $fillable = ['title_en', 'title_jp', 'content_en', 'content_jp', 'source_link'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function kanjis() {
        return $this->belongsToMany(Kanji::class, 'article_kanji');
    }

    public function words() {
        return $this->belongsToMany(Word::class, 'article_word');
    }

}
