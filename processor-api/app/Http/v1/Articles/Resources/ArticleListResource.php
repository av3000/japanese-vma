<?php
namespace App\Http\v1\Articles\Resources;

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
        return $this->collection->map(function ($article) {
            $data = [
                'id' => $article->getUid()->value(),
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

            if ($this->includeStats && $article->hasStats()) {
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
