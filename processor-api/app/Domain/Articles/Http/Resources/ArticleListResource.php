<?php

namespace App\Domain\Articles\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleListResource extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'success' => true,
            'articles' => $this->collection->transform(function ($article) {
                return [
                    'id' => $article->id,
                    'title_jp' => $article->title_jp,
                    'title_en' => $article->title_en,
                    'content_jp' => substr($article->content_jp, 0, 200) . '...',
                    'source_link' => $article->source_link,
                    'jlpt_levels' => [
                        'n1' => $article->n1,
                        'n2' => $article->n2,
                        'n3' => $article->n3,
                        'n4' => $article->n4,
                        'n5' => $article->n5,
                        'uncommon' => $article->uncommon,
                    ],
                    'stats' => [
                        'likesTotal' => $article->likesTotal ?? 0,
                        'downloadsTotal' => $article->downloadsTotal ?? 0,
                        'viewsTotal' => $article->viewsTotal ?? 0,
                        'commentsTotal' => $article->commentsTotal ?? 0,
                    ],
                    'author' => [
                        'id' => $article->user->id,
                        'name' => $article->user->name,
                    ],
                    'hashtags' => $article->hashtags ?? [],
                    'created_at' => $article->created_at->toDateTimeString(),
                ],
            }),
            'message' => 'articles fetched',
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ]
        ];
    }
}
