<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleWordCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'words' => $this->collection,
            'message' => 'Article words fetched'
        ];
    }
}
