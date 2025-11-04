<?php
namespace App\Http\v1\Articles\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ArticleListResource extends ResourceCollection
{
    protected $include_stats;

    public function __construct($resource, $include_stats = false)
    {
        $this->include_stats = $include_stats;
    }

    public function toArray($request)
    {
        return $this->collection->map(function ($article) {
            $data = [
                'id' => $article->value(),
                'title_jp' => $article->getTitleJp()->value(),
                'title_en' => $article->getTitleEn()?->value(),
                'content_preview' => $this->getContentPreview($article->getContentJp()->value()),
                'source_link' => $article->getSourceUrl()->value(),
                'publicity' => $article->getPublicity()->value,
                'status' => $article->getStatus()->value,
                'jlpt_levels' => $article->getJlptLevels()->toArray(),
                'author' => [
                    'id' => $article->getAuthorId()->value(),
                    'name' => $article->getAuthorName()->value(),
                ],
                'hashtags' => $article->getTags()->toArray(),
                'created_at' => $article->getCreatedAt()->format('c'),
                'updated_at' => $article->getUpdatedAt()->format('c'),
            ];

            if ($this->include_stats && $article->hasStats()) {
                $data['engagement'] = [
                    'likes_count' => $article->getLikesCount(),
                    'downloads_count' => $article->getDownloadsCount(),
                    'views_count' => $article->getViewsCount(),
                    'comments_count' => $article->getCommentsCount(),
                ];
            }

            return $data;
        });
    }

    private function getContentPreview(string $content, int $maxLength = 200): string
    {
        if (strlen($content) <= $maxLength) {
            return $content;
        }

        return substr($content, 0, $maxLength) . '...';
    }
}
