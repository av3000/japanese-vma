<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = ['title_en', 'title_jp', 'content_en', 'content_jp', 'source_link'];

    public function user() {
        return $this->belongsTo('App\User');
    }

    public function kanjis() {
        return $this->belongsToMany('App\Kanji', 'article_kanji');
    }

    public function words() {
        return $this->belongsToMany('App\Word', 'article_word');
    }

}
