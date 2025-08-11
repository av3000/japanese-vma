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
    public function execute(ArticleCreateDTO $data, int $userId): Article
    {
        return DB::transaction(function () use ($data, $userId) {
            $article = Article::create([
                'user_id' => $userId,
                'title_jp' => $data->title_jp,
                'title_en' => $data->title_en,
                'content_jp' => $data->content_jp,
                'content_en' => $data->content_en,
                'source_link' => $data->source_link,
                'publicity' => $data->publicity,
            ]);

            $this->attachKanjis->execute($article, $data->content_jp);

            // TODO: implement attachWords
            // $this->attachWords->execute($article, $data->content_jp);

            $this->updateLevels->execute($article);

            // Process and attach hashtags if provided
            if (!empty($data->tags)) {
                $this->attachHashtags->execute($article, $data->tags);
            }

            // Return the complete article with all relationships loaded
            return $article->fresh(['kanjis', 'user']);
        });
    }
}
