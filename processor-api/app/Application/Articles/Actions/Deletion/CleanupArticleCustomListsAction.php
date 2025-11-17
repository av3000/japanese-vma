<?php
namespace App\Application\Articles\Actions\Deletion;

use Illuminate\Support\Facades\DB;
use App\Domain\Shared\Enums\ObjectTemplateType;

class CleanupArticleCustomListsAction
{
    public function execute(int $id): void
    {
        DB::table('customlist_object')
            ->where('real_object_id', $id)
            ->where('listtype_id', ObjectTemplateType::ARTICLE->value)
            ->delete();
    }
}
