<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleKanjiCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'kanjis' => $this->collection,
            'message' => 'Article kanjis fetched'
        ];
    }
}
