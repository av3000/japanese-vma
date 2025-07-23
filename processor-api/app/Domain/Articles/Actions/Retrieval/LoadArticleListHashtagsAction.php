<?php
namespace App\Domain\Articles\Actions;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadArticleListHashtagsAction
{
    /**
     * Load hashtags for a collection of articles.
     * This action has one responsibility: efficiently batch-load hashtag relationships.
     */
    public function execute(LengthAwarePaginator $articles): void
    {
        // Early return if no articles to process
        if ($articles->isEmpty()) {
            return;
        }

        // Get the object template ID for articles
        // Consider caching this value since it rarely changes
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $articleIds = $articles->pluck('id')->toArray();

        // Load and attach hashtags to each article
        $hashtags = $this->batchLoadHashtags($objectTemplateId, $articleIds);

        foreach ($articles as $article) {
            // Attach hashtags or empty array if none found
            $article->hashtags = $hashtags[$article->id] ?? [];
        }
    }

    /**
     * Efficiently load hashtags for multiple articles in two queries instead of N+1
     */
    private function batchLoadHashtags(int $templateId, array $articleIds): array
    {
        // Query 1: Get all hashtag relationships for these articles
        $hashtagLinks = DB::table('hashtags')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->get();

        // Early return if no hashtag relationships found
        if ($hashtagLinks->isEmpty()) {
            return [];
        }

        // Query 2: Get the actual hashtag data
        $uniqueTagIds = $hashtagLinks->pluck('uniquehashtag_id')->unique();
        $uniqueTags = DB::table('uniquehashtags')
            ->whereIn('id', $uniqueTagIds)
            ->get()
            ->keyBy('id'); // Key by ID for efficient lookups

        // Build the final array grouped by article ID
        $result = [];
        foreach ($hashtagLinks as $link) {
            // Initialize array for this article if not exists
            if (!isset($result[$link->real_object_id])) {
                $result[$link->real_object_id] = [];
            }

            // Add the hashtag data if it exists
            if (isset($uniqueTags[$link->uniquehashtag_id])) {
                $result[$link->real_object_id][] = $uniqueTags[$link->uniquehashtag_id];
            }
        }

        return $result;
    }
}
