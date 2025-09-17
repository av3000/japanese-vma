<?php

namespace App\Http\v1\Article\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    public function toArray($request)
    // TODO: now im passing the domain object that I got from service via repository.
    // Should there we a mapper into request? because resource shouldnt know about domain right?
    {
        return [
            'success' => true,
            'article' => [
                'id' => $this->id,
                'title_jp' => $this->title_jp,
                'title_en' => $this->title_en,
                'content_jp' => $this->content_jp,
                'content_en' => $this->content_en,
                'source_link' => $this->source_link,
                'publicity' => $this->publicity->value,
                'status' => $this->status->value,
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
                // Detail-specific data
                'comments' => $this->comments ?? [],
                'kanjis' => $this->kanjis ?? [],
                'words' => $this->words ?? [],
            ],
            'message' => 'Article details fetched',
        ];
    }
}
