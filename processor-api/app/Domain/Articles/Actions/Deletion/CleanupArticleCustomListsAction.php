<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;

class CleanupArticleCustomListsAction
{
    public function execute(Article $article): void
    {
        // Remove from custom lists
        // TODO: Create well defined enums for static uids
        DB::table('customlist_object')
            ->where('real_object_id', $article->id)
            ->where('listtype_id', ObjectTemplateType::ARTICLES->value)
            ->delete();
    }
}
