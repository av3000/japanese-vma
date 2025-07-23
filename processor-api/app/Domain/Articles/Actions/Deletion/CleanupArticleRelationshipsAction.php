<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;

class CleanupArticleRelationshipsAction
{
    public function execute(Article $article): void
    {
        // Detach many-to-many relationships
        $article->kanjis()->detach();
        $article->words()->detach();
    }
}
