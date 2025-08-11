<?php
namespace App\Domain\Articles\Actions;

use App\Domain\Articles\DTOs\ArticleUpdateDTO;
use App\Domain\Articles\Interfaces\Actions\UpdateArticleActionInterface;
use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;
use App\Domain\Articles\Actions\Updates\UpdateArticleFieldsAction;
use App\Domain\Articles\Actions\Updates\UpdateArticleHashtagsAction;
use App\Domain\Articles\Actions\Updates\ReprocessArticleDataAction;

class UpdateArticleAction implements UpdateArticleActionInterface
{
    public function __construct(
        private UpdateArticleFieldsAction $updateFields,
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
            \Log::info('Should reprocess: ' . ($data->hasContentChanges() ? 'yes' : 'no'));

            $this->updateFields->execute($article, $data);

            if ($data->tags !== null) {
                $this->updateHashtags->execute($article, $data->tags);
            }

            if ($data->hasContentChanges()) {
                $this->reprocessData->execute($article);
            }

            return $article->fresh(['kanjis', 'user']);
        });
    }
}
