<?php
namespace App\Domain\Articles\Actions\Creation;

use App\Domain\Articles\DTOs\ArticleCreateDTO;
use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Interfaces\Actions\CreateArticleActionInterface;
use Illuminate\Support\Facades\DB;
use App\Domain\Articles\Actions\Creation\AttachKanjisAction;
use App\Domain\Articles\Actions\Creation\UpdateJLPTLevelsAction;
use App\Domain\Articles\Actions\Creation\AttachHashtagsAction;

class CreateArticleAction implements CreateArticleActionInterface
{
    public function __construct(
        private AttachKanjisAction $attachKanjis,
        private UpdateJLPTLevelsAction $updateLevels,
        private AttachHashtagsAction $attachHashtags
    ) {}

    /**
     * Create a complete article with all associated data.
     * This method demonstrates transaction handling - ensuring all steps
     * complete successfully or none at all, maintaining data consistency.
     */

    public function execute(ArticleCreateDTO $articleCreateDTO, int $userId): Article
    {
        return DB::transaction(function () use ($articleCreateDTO, $userId) {
            // creating rich domain object with validation
            $article = Article::createFromDTO($articleCreateDTO, $userId);

            $this->attachKanjis->execute($article, $articleCreateDTO->content_jp);

            // TODO: implement attachWords
            // $this->attachWords->execute($article, $articleCreateDTO->content_jp);

            $this->updateLevels->execute($article);

            // Process and attach hashtags if provided
            if (!empty($articleCreateDTO->tags)) {
                $this->attachHashtags->execute($article, $articleCreateDTO->tags);
            }

            // Return the complete article with all relationships loaded
            return $article->fresh(['kanjis', 'user']);
        });
    }
}
