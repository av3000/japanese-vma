<?php

namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class CleanupArticleData
{
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        // Detach relationships
        $article->kanjis()->detach();
        $article->words()->detach();

        // Remove impressions
        removeImpressions($article, $objectTemplateId);

        // Remove hashtags
        removeHashtags($article->id, $objectTemplateId);

        // TODO: figure if should be an eloquent method
        // Remove from custom lists
        DB::table('customlist_object')
            ->where('real_object_id', $article->id)
            ->where('listtype_id', 9)
            ->delete();
    }
}
