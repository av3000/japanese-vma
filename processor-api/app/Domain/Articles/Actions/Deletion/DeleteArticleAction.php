<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;
use App\Domain\Articles\Actions\Deletion\CleanupArticleRelationshipsAction;
use App\Domain\Articles\Actions\Deletion\CleanupArticleEngagementAction;
use App\Domain\Articles\Actions\Deletion\CleanupArticleHashtagsAction;
use App\Domain\Articles\Actions\Deletion\CleanupArticleCustomListsAction;
use App\Domain\Articles\Interfaces\Actions\DeleteArticleActionInterface;

class DeleteArticleAction implements DeleteArticleActionInterface
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

            if ($article->user_id !== $userId && !$isAdmin) {
                return false;
            }

            $this->cleanupRelationships->execute($article);
            $this->cleanupEngagement->execute($article);
            $this->cleanupHashtags->execute($article);
            $this->cleanupCustomLists->execute($article);

            $article->delete();

            return true;
        });
    }
}
