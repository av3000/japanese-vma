<?php

namespace App\Http\v1\Articles\Resources;

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
