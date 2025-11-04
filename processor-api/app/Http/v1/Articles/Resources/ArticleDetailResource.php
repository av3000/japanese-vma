<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Engagement\Models\EngagementData;
use App\Http\v1\Comments\Resources\CommentResource;

use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    private ?EngagementData $engagementData;

    public function __construct(
        $article,
        ?EngagementData $engagementData = null,
        private array $kanjis = [],
        private array $words = [],
        array $hashtags = []
    ) {
        parent::__construct($article);
        $this->engagementData = $engagementData;
        $this->kanjis = $kanjis;
        $this->words = $words;
        $this->hashtags = $hashtags;
    }

    /**
     * Transform the article domain model into an API representation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    // TODO: now im passing the domain object that I got from service via repository.
    // Should there we a mapper into request? because resource shouldnt know about domain right?
    {
        return [
            'article' => [
                'id' => $this->getIdValue(),
                'uid' => (string) $this->getUid(),
                'entity_type_uid' => (string) $this->getEntityTypeUid(),
                'title_jp' => $this->getTitleJp()->value,
                'title_en' => $this->getTitleEn()?->value,
                'content_jp' => $this->getContentJp()->value,
                'content_en' => $this->getContentEn()?->value,
                'source_link' => $this->getSourceUrl()->value,
                'publicity' => $this->getPublicity()->value,
                'status' => $this->getStatus()->value,
                'jlpt_levels' => $this->getJlptLevels()->toArray(),
                'author' => [
                    'id' => $this->getAuthorId()->value(),
                    'name' => $this->getAuthorName()->value(),
                ],
                'hashtags' => $this->hashtags,
                'created_at' => $this->getCreatedAt()->format('c'),
                'updated_at' => $this->getUpdatedAt()->format('c'),
                'engagement' =>
                    $this->engagementData ? [
                            'likes' => $this->engagementData?->getLikes(),
                            'views' => $this->engagementData?->getViews(),
                            'downloads' => $this->engagementData?->getDownloads(),
                            'comments' => $this->engagementData?->getComments() ? array_map(function($comment) {
                                    return (new CommentResource(
                                        comment: $comment,
                                        include_likes: false,
                                        include_replies: false
                                    ))->toArray(request());
                                }, $this->engagementData->getComments())
                                : [],
                    ] : null,
                'kanjis' => $this->kanjis,
                'words' => $this->words,
            ],
        ];
    }
}
