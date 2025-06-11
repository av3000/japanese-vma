<?php

namespace App\Domain\Articles\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\{Like, Download, View, Comment, ObjectTemplate};

class LoadArticleStats
{
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        $article->likesTotal = $this->getCount(Like::class, $objectTemplateId, $article->id);
        $article->downloadsTotal = $this->getCount(Download::class, $objectTemplateId, $article->id);
        $article->viewsTotal = $this->getCount(View::class, $objectTemplateId, $article->id);
        $article->commentsTotal = $this->getCount(Comment::class, $objectTemplateId, $article->id);
        $article->hashtags = getUniquehashtags($article->id, $objectTemplateId);

        // Calculate kanji stats
        $article->jlptcommon = $article->kanjis->where('jlpt', '-')->count();
        $article->kanjiTotal = collect(['n1', 'n2', 'n3', 'n4', 'n5'])
            ->sum(fn($level) => $article->$level) + $article->jlptcommon;
    }

    private function getCount(string $model, int $templateId, int $objectId): int
    {
        return $model::where([
            'template_id' => $templateId,
            'real_object_id' => $objectId
        ])->count();
    }
}
