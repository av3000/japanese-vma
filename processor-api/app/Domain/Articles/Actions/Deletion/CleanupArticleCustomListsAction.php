<?php
namespace App\Domain\Articles\Actions\Deletion;

use App\Domain\Articles\Models\Article;
use Illuminate\Support\Facades\DB;

class CleanupArticleCustomListsAction
{
    public function execute(Article $article): void
    {
        // Remove from custom lists
        // TODO: Replace magic number 9 with a constant or lookup
        DB::table('customlist_object')
            ->where('real_object_id', $article->id)
            ->where('listtype_id', 9)
            ->delete();
    }
}
