<?php
namespace App\Domain\Articles\Actions\Retrieval;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;
use App\Domain\Articles\Interfaces\IHashtagLoader;

class LoadArticleListHashtagsAction implements IHashtagLoader
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
        $hashtagLinks = DB::table('hashtags')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->get();

        if ($hashtagLinks->isEmpty()) {
            return [];
        }

        // Get the actual hashtag data
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
