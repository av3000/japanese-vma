<?php
namespace App\Application\Articles\Actions\Deletion;

use App\Domain\Articles\Http\Models\Article;

class CleanupArticleRelationshipsAction
{
    public function execute(Article $article): void
    {
        // Detach many-to-many relationships
        $article->kanjis()->detach();
        $article->words()->detach();
    }
}
