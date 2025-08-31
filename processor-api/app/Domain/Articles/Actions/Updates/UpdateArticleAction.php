<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\Interfaces\Actions\UpdateArticleActionInterface;
use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;
use App\Domain\Articles\Actions\Updates\UpdateArticleHashtagsAction;
use App\Domain\Articles\Actions\Updates\ReprocessArticleDataAction;

class UpdateArticleAction implements UpdateArticleActionInterface
{
    public function __construct(
        private UpdateArticleHashtagsAction $updateHashtags,
        private ReprocessArticleDataAction $reprocessData
    ) {}

    public function execute(int $id, ArticleUpdateDTO $data, int $userId): ?Article
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $article = Article::where('id', $id)->where('user_id', $userId)->first();
            if (!$article) {
                return null;
            }

            \Log::info('Update start for article: ' . $id);

            $article->updateFromDTO($data);

            if ($data->tags !== null) {
                $this->updateHashtags->execute($article, $data->tags);
            }

            if ($article->shouldReprocessContent($data)) {
                \Log::info('Reprocessing content for article: ' . $id);
                $this->reprocessData->execute($article);
            }

            $article->save();

            return $article->fresh(['kanjis', 'user']);
        });
    }
}
