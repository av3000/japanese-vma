<?php

namespace App\Application\Articles\Actions\Retrieval;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class LoadArticleListHashtagsAction
{
    /**
     * Load hashtags for a collection of articles.
     */
    public function execute(LengthAwarePaginator $articles): void
    {
        if ($articles->isEmpty()) {
            return;
        }

        // Consider caching this value since it rarely changes
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $articleIds = $articles->pluck('id')->toArray();

        $hashtags = $this->batchLoadHashtags($objectTemplateId, $articleIds);

        foreach ($articles as $article) {
            $article->hashtags = $hashtags[$article->id] ?? [];
        }
    }

    /**
     * Efficiently load hashtags for multiple articles in two queries instead of N+1
     */
    private function batchLoadHashtags(int $templateId, array $articleIds): array
    {
        $hashtagLinks = DB::table('hashtag_entity')
            ->where('entity_type_id', $templateId)
            ->whereIn('entity_id', $articleIds)
            ->get();

        if ($hashtagLinks->isEmpty()) {
            return [];
        }

        // Get the actual hashtag data
        $uniqueTagIds = $hashtagLinks->pluck('hashtag_id')->unique();
        $uniqueTags = DB::table('uniquehashtags')
            ->whereIn('id', $uniqueTagIds)
            ->get()
            ->keyBy('id'); // Key by ID for efficient lookups

        // Build the final array grouped by article ID
        $result = [];
        foreach ($hashtagLinks as $link) {
            // Initialize array for this article if not exists
            if (!isset($result[$link->entity_id])) {
                $result[$link->entity_id] = [];
            }

            // Add the hashtag data if it exists
            if (isset($uniqueTags[$link->hashtag_id])) {
                $result[$link->entity_id][] = $uniqueTags[$link->hashtag_id];
            }
        }

        return $result;
    }
}
