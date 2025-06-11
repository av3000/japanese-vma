<?php

namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;

class CalculateJLPTLevels
{
    public function execute(Article $article): array
    {
        $levels = [
            'n1' => 0,
            'n2' => 0,
            'n3' => 0,
            'n4' => 0,
            'n5' => 0,
            'uncommon' => 0,
        ];

        foreach ($article->kanjis as $kanji) {
            $jlptLevel = $kanji->jlpt;

            if (in_array($jlptLevel, ['1', '2', '3', '4', '5'])) {
                $field = 'n' . $jlptLevel;
                $levels[$field]++;
            } else {
                $levels['uncommon']++;
            }
        }

        return $levels;
    }
}
