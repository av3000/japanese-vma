<?php

namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleWordCollection extends ResourceCollection
{
    public $collects = ArticleWordResource::class;

    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'words' => $this->collection,
            'message' => 'Article words fetched',
        ];
    }
}
