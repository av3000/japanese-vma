<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title_jp' => $this->title_jp,
            'title_en' => $this->title_en,
            'content_jp' => $this->content_jp,
            'content_en' => $this->content_en,
            'source_link' => $this->source_link,
            'publicity' => $this->publicity,
            'status' => $this->status,
            'jlpt_levels' => [
                'n1' => $this->n1,
                'n2' => $this->n2,
                'n3' => $this->n3,
                'n4' => $this->n4,
                'n5' => $this->n5,
                'uncommon' => $this->uncommon,
            ],
            'author' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
