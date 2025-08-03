<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Articles\DTOs\ArticleDTO;

class ArticleDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'article' => ArticleDTO::fromModel($this)->toDetailArray(),
            'message' => 'Article details fetched',
        ];
    }
}
