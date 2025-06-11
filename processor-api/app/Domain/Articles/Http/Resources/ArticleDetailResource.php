<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    public function toArray($request)
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
                'publicity' => $this->publicity,
                'status' => $this->status,
                'jlpt_levels' => [
                    'n1' => $this->n1,
                    'n2' => $this->n2,
                    'n3' => $this->n3,
                    'n4' => $this->n4,
                    'n5' => $this->n5,
                    'uncommon' => $this->uncommon,
                    'jlptcommon' => $this->jlptcommon ?? 0,
                    'kanjiTotal' => $this->kanjiTotal ?? 0,
                ],
                'stats' => [
                    'likesTotal' => $this->likesTotal ?? 0,
                    'downloadsTotal' => $this->downloadsTotal ?? 0,
                    'viewsTotal' => $this->viewsTotal ?? 0,
                    'commentsTotal' => $this->commentsTotal ?? 0,
                ],
                'author' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ],
                'hashtags' => $this->hashtags ?? [],
                'comments' => $this->comments ?? [],
                'kanjis' => $this->kanjis ?? [],
                'words' => $this->words ?? [],
                'created_at' => $this->created_at->toDateTimeString(),
                'updated_at' => $this->updated_at->toDateTimeString(),
            ]
        ];
    }
}
