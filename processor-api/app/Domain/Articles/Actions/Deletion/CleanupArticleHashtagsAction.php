<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Domain\Articles\Models\Article;
use App\Http\Models\ObjectTemplate;
use Illuminate\Support\Facades\DB;

class CleanupArticleHashtagsAction
{
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;

        DB::table('hashtags')
            ->where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)
            ->delete();
    }
}
