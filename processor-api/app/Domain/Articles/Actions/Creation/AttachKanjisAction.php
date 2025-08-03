<?php
namespace App\Domain\Articles\Actions\Creation;

use App\Domain\Articles\Models\Article;

class AttachKanjisAction
{
    public function __construct(
        private ExtractKanjis $extractKanjis
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
