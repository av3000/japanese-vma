<?php
namespace App\Domain\Articles\Services;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\DTOs\ArticleData;
use App\Domain\Articles\DTOs\ArticleUpdateData;
use App\Domain\Articles\Actions\ExtractKanjis;
use App\Domain\Articles\Actions\CalculateJLPTLevels;
use App\Domain\Articles\Actions\CleanupArticleData;
use App\Domain\Articles\Actions\IncrementView;
use App\Domain\Articles\Actions\LoadArticleStats;
use App\Domain\Articles\Actions\LoadArticleListStats;
use App\Domain\Articles\Actions\ProcessWordMeanings;
use App\Domain\Articles\Actions\LoadComments;
use App\Http\Models\ObjectTemplate;
use App\Domain\Articles\DTOs\ArticleIndexData;
use Illuminate\Pagination\LengthAwarePaginator;


class ArticleService
{
    public function __construct(
        private ExtractKanjis $extractKanjis,
        private CalculateJLPTLevels $calculateLevels,
        private IncrementView $incrementView,
        private LoadArticleStats $loadStats,
        private LoadArticleListStats $loadListStats,
        private ProcessWordMeanings $processWords,
        private LoadComments $loadComments,
        private CleanupArticleData $cleanupArticleData,
    ) {}

    public function getArticles(ArticleIndexData $data): LengthAwarePaginator
    {
        $query = Article::query()
            ->where('publicity', 1)
            ->with('user');

        if ($data->category !== null) {
            $query->where('category_id', $data->category);
        }

        if ($data->search !== null) {
            $query->where(function($q) use ($data) {
                $q->where('title_jp', 'LIKE', '%' . $data->search . '%')
                ->orWhere('title_en', 'LIKE', '%' . $data->search . '%');
            });
        }

        $articles = $query
            ->orderBy($data->sortBy, $data->sortDir)
            ->paginate($data->perPage);

        $this->loadListStats->execute($articles);

        return $articles;
    }

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

        $kanjiIds = $this->extractKanjis->execute($data->content_jp);
        $article->kanjis()->attach($kanjiIds);

        // TODO: implement word extraction and attachment
        // $wordIds = $this->extractWords->execute($data->content_jp);
        // $article->words()->attach($wordIds);

        $levels = $this->calculateLevels->execute($article);
        $article->update($levels);

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

        $this->incrementView->execute($article);

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

        // TODO: Reprocess kanjis/JLPT if needed
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

        $levels = $this->calculateLevels->execute($article);
        $article->update($levels);
    }

    public function deleteArticle(int $id, int $userId, bool $isAdmin = false): bool
    {
        $article = Article::find($id);

        if (!$article) {
            return false;
        }

        if ($article->user_id !== $userId && !$isAdmin) {
            return false;
        }

        $this->cleanupArticleData->execute($article);

        $article->delete();

        return true;
    }

    private function removeFromLists(int $articleId): void
    {
        // Remove article from custom lists (type 9 for custom list entity)
        \DB::table('customlist_object')
            ->where('real_object_id', $articleId)
            ->where('listtype_id', 9)
            ->delete();
    }

}
