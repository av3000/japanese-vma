<?php
namespace App\Domain\Articles\Actions\Processing;

use App\Infrastructure\Persistence\Models\Article;;

class UpdateJLPTLevelsAction
{
    public function __construct(
        private CalculateJLPTLevels $calculateLevels
    ) {}

    /**
     * Calculate and update JLPT levels for the article.
     * This updates the article model with difficulty metrics.
     */
    public function execute(Article $article): void
    {
        $levels = $this->calculateLevels->execute($article);
        $article->update($levels);
    }
}
