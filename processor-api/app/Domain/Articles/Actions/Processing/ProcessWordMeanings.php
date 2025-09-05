<!-- TODO: Use only ProcessWordMeaningsAction and remove this redundant old implementation-->

<?php

namespace App\Domain\Articles\Actions\Processing;

use App\Domain\Articles\Http\Models\Article;

class ProcessWordMeanings
{
    public function execute(Article $article): void
    {
         \Log::info('Words count: ' . $article->words->count());
        $start = microtime(true);

        $words = $article->words;

        foreach ($words as $word) {
            if ($index % 100 === 0) {
                \Log::info("Processing word $index, time: " . round(microtime(true) - $start, 2) . 's');
            }
            $senseData = json_decode($word->sense, true);
            $meanings = [];

            foreach ($senseData as $sense) {
                foreach ($sense as $item) {
                    if ($item[0] === 'gloss' && isset($item[1])) {
                        $glossData = is_array($item[1]) ? $item[1] : [$item[1]];
                        $meanings = array_merge($meanings, $glossData);
                    }
                }
            }

            $word->meaning = implode(', ', array_slice($meanings, 0, 3));
        }

        \Log::info('Word processing completed in: ' . round(microtime(true) - $start, 2) . 's');

        $article->words = $words;
    }
}
