<?php

namespace App\Domain\Articles\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Models\ObjectTemplate;

class LoadArticleListStats
{
    public function execute(LengthAwarePaginator $articles): void
    {
        if ($articles->isEmpty()) {
            return;
        }

        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $articleIds = $articles->pluck('id')->toArray();

        // Batch load stats to avoid N+1 queries
        $stats = $this->batchLoadStats($objectTemplateId, $articleIds);
        $hashtags = $this->batchLoadHashtags($objectTemplateId, $articleIds);

        foreach ($articles as $article) {
            $article->likesTotal = $stats['likes'][$article->id] ?? 0;
            $article->downloadsTotal = $stats['downloads'][$article->id] ?? 0;
            $article->viewsTotal = $stats['views'][$article->id] ?? 0;
            $article->commentsTotal = $stats['comments'][$article->id] ?? 0;
            $article->hashtags = $hashtags[$article->id] ?? [];
        }
    }

    private function batchLoadStats(int $templateId, array $articleIds): array
    {
        $likes = \DB::table('likes')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(\DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $downloads = \DB::table('downloads')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(\DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $views = \DB::table('views')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(\DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        $comments = \DB::table('comments')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->groupBy('real_object_id')
            ->pluck(\DB::raw('count(*)'), 'real_object_id')
            ->toArray();

        return [
            'likes' => $likes,
            'downloads' => $downloads,
            'views' => $views,
            'comments' => $comments,
        ];
    }

    private function batchLoadHashtags(int $templateId, array $articleIds): array
    {
        $hashtagLinks = \DB::table('hashtags')
            ->where('template_id', $templateId)
            ->whereIn('real_object_id', $articleIds)
            ->get();

        $uniqueTagIds = $hashtagLinks->pluck('uniquehashtag_id')->unique();
        $uniqueTags = \DB::table('uniquehashtags')
            ->whereIn('id', $uniqueTagIds)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach ($hashtagLinks as $link) {
            if (!isset($result[$link->real_object_id])) {
                $result[$link->real_object_id] = [];
            }
            if (isset($uniqueTags[$link->uniquehashtag_id])) {
                $result[$link->real_object_id][] = $uniqueTags[$link->uniquehashtag_id];
            }
        }

        return $result;
    }
}
