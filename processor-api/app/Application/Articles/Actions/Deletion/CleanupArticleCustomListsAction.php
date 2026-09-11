<?php

namespace App\Application\Articles\Actions\Deletion;

use App\Domain\Shared\Enums\ObjectTemplateType;
use Illuminate\Support\Facades\DB;

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
