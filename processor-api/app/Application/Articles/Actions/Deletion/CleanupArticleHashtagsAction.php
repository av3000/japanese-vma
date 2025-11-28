<?php

namespace App\Application\Articles\Actions\Deletion;

use App\Domain\Articles\Http\Models\Article;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class CleanupArticleHashtagsAction
{
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        DB::table('hashtag_entity')
            ->where('entity_type_id', $objectTemplateId)
            ->where('entity_id', $article->id)
            ->delete();
    }
}
