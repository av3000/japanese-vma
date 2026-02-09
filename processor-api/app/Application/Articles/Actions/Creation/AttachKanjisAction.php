<?php

namespace App\Application\Articles\Actions\Creation;

use App\Application\Articles\Actions\Processing\ExtractKanjisAction;
use App\Infrastructure\Persistence\Models\Article;

class AttachKanjisAction
{
    public function __construct(
        private ExtractKanjisAction $extractKanjis
    ) {}

    /**
     * Extract kanjis from text and attach them to the article.
     * This encapsulates the kanji extraction and relationship creation.
     */
    public function execute(Article $article, string $content): void
    {
        $kanjiIds = $this->extractKanjis->execute($content);
        $article->kanjis()->attach($kanjiIds);
    }
}
