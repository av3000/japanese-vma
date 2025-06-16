<?php
namespace App\Domain\Articles\Services;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\DTOs\ArticleData;
use App\Domain\Articles\DTOs\ArticleUpdateData;
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

    public function updateArticle(int $id, ArticleUpdateData $data, int $userId): ?Article
    {
        $article = Article::where('id', $id)->where('user_id', $userId)->first();

        if (!$article) {
            return null;
        }

        // Track what changed for reprocessing logic
        $shouldReprocess = $data->reattach || $data->hasContentChanges();
        \Log::info('Update start for article: ' . $id);
        \Log::info('Should reprocess: ' . ($shouldReprocess ? 'yes' : 'no'));

        // Update fields
        $this->updateFields($article, $data);

        // Handle tags
        if ($data->tags !== null) {
            $this->updateTags($article, $data->tags);
        }

        // Reprocess kanjis/JLPT if needed
        // if ($shouldReprocess) {
        //     $this->reprocessKanjisAndLevels($article);
        // }

        return $article->fresh(['kanjis', 'user']);
    }

    private function updateFields(Article $article, ArticleUpdateData $data): void
    {
        $fields = ['title_jp', 'title_en', 'content_jp', 'content_en', 'source_link', 'publicity', 'status'];

        foreach ($fields as $field) {
            if ($data->$field !== null) {
                $article->$field = $data->$field;
            }
        }

        $article->save();
    }

    private function updateTags(Article $article, array $tags): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        removeHashtags($article->id, $objectTemplateId);

        if (!empty($tags)) {
            $tagsString = implode(' ', $tags);
            attachHashTags($tagsString, $article, $objectTemplateId);
        }
    }

    private function reprocessKanjisAndLevels(Article $article): void
    {
        // Detach existing kanjis
        $article->kanjis()->detach();
        $article->words()->detach();

        // Re-extract and attach
        $kanjiIds = $this->extractKanjis->execute($article->content_jp);
        $article->kanjis()->attach($kanjiIds);

        // Recalculate JLPT levels
        $levels = $this->calculateLevels->execute($article);
        $article->update($levels);
    }

    public function deleteArticle(int $id, int $userId, bool $isAdmin = false): bool
    {
        $article = Article::find($id);

        if (!$article) {
            return false;
        }

        // Authorization check
        if ($article->user_id !== $userId && !$isAdmin) {
            return false;
        }

        // Clean up all related data
        $this->cleanupArticleData($article);

        // Delete the article
        $article->delete();

        return true;
    }
    // TODO: Figure if should be an action
    // private function cleanupArticleData(Article $article): void
    // {
    //     $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

    //     // Detach relationships
    //     $article->kanjis()->detach();
    //     $article->words()->detach();

    //     // Remove impressions (likes, views, comments)
    //     removeImpressions($article, $objectTemplateId);

    //     // Remove hashtags
    //     removeHashtags($article->id, $objectTemplateId);

    //     // Remove from custom lists
    //     $this->removeFromLists($article->id);
    // }

    private function removeFromLists(int $articleId): void
    {
        // Remove article from custom lists (type 9 based on your seeder)
        \DB::table('customlist_object')
            ->where('real_object_id', $articleId)
            ->where('listtype_id', 9)
            ->delete();
    }

}
