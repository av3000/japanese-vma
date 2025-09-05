<?php
namespace App\Http\v1\Article\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleListResource extends ResourceCollection
{
    protected $includeStats;

    public function __construct($resource, $includeStats = false)
    {
        parent::__construct($resource);
        $this->includeStats = $includeStats;
    }

    public function toArray($request)
    {
        return [
            'success' => true,
            'articles' => $this->collection->map(function ($article) {
                $data = [
                    'id' => $article->id,
                    'title_jp' => $article->title_jp,
                    'title_en' => $article->title_en,
                    'content_jp' => $article->content_jp,
                    'content_en' => $article->content_en,
                    'source_link' => $article->source_link,
                    'publicity' => $article->publicity->value,
                    'status' => $article->status->value,
                    'jlpt_levels' => [
                        'n1' => $article->n1,
                        'n2' => $article->n2,
                        'n3' => $article->n3,
                        'n4' => $article->n4,
                        'n5' => $article->n5,
                        'uncommon' => $article->uncommon,
                    ],
                    'author' => [
                        'id' => $article->user->id,
                        'name' => $article->user->name,
                    ],
                    'hashtags' => $article->hashtags ?? [],
                    'created_at' => $article->created_at->toDateTimeString(),
                    'updated_at' => $article->updated_at->toDateTimeString(),
                ];

                if ($this->includeStats) {
                    $data['stats'] = [
                        'likesTotal' => $article->likesTotal ?? 0,
                        'downloadsTotal' => $article->downloadsTotal ?? 0,
                        'viewsTotal' => $article->viewsTotal ?? 0,
                        'commentsTotal' => $article->commentsTotal ?? 0,
                    ];
                }

                return $data;
            }),
            'message' => 'Articles fetched'
        ];
    }
}
