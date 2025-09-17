<?php
namespace App\Application\Articles\Actions\Retrieval;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadArticleListStatsAction
{
    /**
     * Load statistical data (likes, downloads, views, comments) for a collection of articles.
     * This action has one responsibility: efficiently batch-load engagement statistics.
     */
    public function execute(LengthAwarePaginator $articles): void
    {
        // Early return if no articles to process
        if ($articles->isEmpty()) {
            return;
        }

        // Get the object template ID for articles
        // TODO: Consider caching this lookup since it rarely changes
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $articleIds = $articles->pluck('id')->toArray();

        // Load all stats in one method call
        $stats = $this->batchLoadStats($objectTemplateId, $articleIds);

        // Attach stats to each article with safe defaults
        foreach ($articles as $article) {
            $article->likesTotal = $stats['likes'][$article->id] ?? 0;
            $article->downloadsTotal = $stats['downloads'][$article->id] ?? 0;
            $article->viewsTotal = $stats['views'][$article->id] ?? 0;
            $article->commentsTotal = $stats['comments'][$article->id] ?? 0;
        }
    }

    /**
     * Execute four optimized queries to get all statistical data.
     * This is more efficient than N+1 queries but could be further optimized
     * by combining into a single query with subqueries if performance becomes critical.
     */
    private function batchLoadStats(int $templateId, array $articleIds): array
    {
        // Execute all stat queries in parallel conceptually
        // Each query is optimized with proper WHERE clauses and GROUP BY

        $likes = DB::table('likes')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $downloads = DB::table('downloads')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $views = DB::table('views')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $comments = DB::table('comments')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        return [
            'likes' => $likes,
            'downloads' => $downloads,
            'views' => $views,
            'comments' => $comments,
        ];
    }
}
