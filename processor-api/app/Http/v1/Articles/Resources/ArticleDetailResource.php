<?php

namespace App\Http\v1\Articles\Resources;

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
                'id' => $this->getIdValue(),
                'title_jp' => $this->getTitleJp()->value,
                'title_en' => $this->getTitleEn()->value,
                'content_jp' => $this->getContentJp()->value,
                'content_en' => $this->getContentEn()->value,
                'source_link' => $this->getSourceUrl()->value,
                'publicity' => $this->getPublicity()->value,
                'status' => $this->getStatus()->value,
                'jlpt_levels' => $this->getJlptLevels()->toArray(),
                'author' => [
                    'id' => $article->getAuthorId()->value(),
                    'name' => $article->getAuthorName()->value(),
                ],
                'hashtags' => $article->getTags()->toArray(),
                'created_at' => $article->getCreatedAt()->format('c'),
                'updated_at' => $article->getUpdatedAt()->format('c'),
                'likes' => $this->when($this->include_likes, $this->likes),
                'comments' => $this->when($this->include_comments, $this->comments),
                'views' => $this->when($this->include_views, $this->views),
                'downloads' => $this->when($this->include_downloads, $this->downloads),
                'kanjis' => $this->when($this->include_kanjis, $this->kanjis),
                'words' => $this->when($this->include_words, $this->words),
            ],
            'message' => 'Article details fetched',
        ];
    }
}
