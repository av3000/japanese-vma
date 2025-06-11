<?php
namespace App\Domain\Articles\Services;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\DTOs\ArticleData;
use App\Domain\Articles\Actions\ExtractKanjis;
use App\Domain\Articles\Actions\CalculateJLPTLevels;
use App\Domain\Articles\Actions\IncrementView;
use App\Domain\Articles\Actions\LoadArticleStats;
use App\Domain\Articles\Actions\ProcessWordMeanings;
use App\Domain\Articles\Actions\LoadComments;
use App\Http\Models\ObjectTemplate;

class ArticleService
{
    public function __construct(
        private ExtractKanjis $extractKanjis,
        private CalculateJLPTLevels $calculateLevels,
        private IncrementView $incrementView,
        private LoadArticleStats $loadStats,
        private ProcessWordMeanings $processWords,
        private LoadComments $loadComments
    ) {}

    public function createArticle(ArticleData $data, int $userId): Article
    {
        $article = Article::create([
            'user_id' => $userId,
            'title_jp' => $data->title_jp,
            'title_en' => $data->title_en,
            'content_jp' => $data->content_jp,
            'content_en' => $data->content_en,
            'source_link' => $data->source_link,
            'publicity' => $data->publicity,
        ]);

        // Extract and attach kanjis
        $kanjiIds = $this->extractKanjis->execute($data->content_jp);
        $article->kanjis()->attach($kanjiIds);

        // TODO: implement word extraction and attachment
        // $wordIds = $this->extractWords->execute($data->content_jp);
        // $article->words()->attach($wordIds);

        // Calculate JLPT levels
        $levels = $this->calculateLevels->execute($article);
        $article->update($levels);

        // Handle tags
        if (!empty($data->tags)) {
            $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
            $tagsString = implode(' ', $data->tags);
            attachHashTags($tagsString, $article, $objectTemplateId);
        }

        return $article->fresh(['kanjis', 'user']);
    }

    public function getArticleWithDetails(int $id): ?Article
    {
        $article = Article::with(['user', 'kanjis', 'words'])->find($id);

        if (!$article) {
            return null;
        }

        // Increment view
        $this->incrementView->execute($article);

        // Load stats and additional data
        $this->loadStats->execute($article);
        $this->processWords->execute($article);
        $this->loadComments->execute($article);

        return $article;
    }
}
