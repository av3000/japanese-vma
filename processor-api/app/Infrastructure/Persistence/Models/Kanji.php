<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Kanji extends Model
{
    protected $table = "japanese_kanji_bank_long";

    public $timestamps = false;

    protected $fillable = [
        'kanji',
        'onyomi',
        'kunyomi',
        'meaning',
        'nanori',
        'grade',
        'stroke_count',
        'jlpt',
        'frequency',
        'radicals',
        'radical_parts'
    ];

    protected $casts = [
        'kanji'        => 'string',
        'stroke_count' => 'integer',
        'frequency' => 'integer',
        // Keep these as strings since they often contain multiple values
        // separated by delimiters (like commas or semicolons)
        'onyomi' => 'string',
        'kunyomi' => 'string',
        'meaning' => 'string',
        'nanori' => 'string',
        'grade' => 'string',
        'jlpt' => 'string',
        'radicals' => 'string',
        'radical_parts' => 'string'
    ];

    public function words()
    {
        return $this->belongsToMany(Word::class, 'japanese_kanji_word_long');
    }

    public function sentences()
    {
        return $this->belongsToMany(Sentence::class, 'japanese_sentence_kanji');
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_kanji');
    }

    public function radicals()
    {
        return $this->belongsToMany(Radical::class, 'japanese_radical_kanji_long');
    }

    public function scopeByGrade(Builder $query, string $grade)
    {
        return $query->where('grade', $grade);
    }

    public function scopeByJlptLevel(Builder $query, string $level)
    {
        return $query->where('jlpt', $level);
    }
}
