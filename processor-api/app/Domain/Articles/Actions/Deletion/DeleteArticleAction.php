<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;

class DeleteArticleAction
{
    public function __construct(
        private CleanupArticleRelationshipsAction $cleanupRelationships,
        private CleanupArticleEngagementAction $cleanupEngagement,
        private CleanupArticleHashtagsAction $cleanupHashtags,
        private CleanupArticleCustomListsAction $cleanupCustomLists
    ) {}

    public function execute(int $id, int $userId, bool $isAdmin = false): bool
    {
        return DB::transaction(function () use ($id, $userId, $isAdmin) {
            $article = Article::find($id);
            if (!$article) {
                return false;
            }

            // Check authorization
            if ($article->user_id !== $userId && !$isAdmin) {
                return false;
            }

            // Clean up all related data before deletion
            $this->cleanupRelationships->execute($article);
            $this->cleanupEngagement->execute($article);
            $this->cleanupHashtags->execute($article);
            $this->cleanupCustomLists->execute($article);

            // Delete the article
            $article->delete();

            return true;
        });
    }
}
