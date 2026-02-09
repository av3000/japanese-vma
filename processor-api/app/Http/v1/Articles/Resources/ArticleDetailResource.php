<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    private ?EngagementSummary $engagement;

    public function __construct(
        $article,
        ?EngagementSummary $engagement = null,
        private array $kanjis = [],
        private array $words = [],
        private array $hashtags = [],
        private ?LastOperationState $lastOperation = null
    ) {
        parent::__construct($article);
        $this->engagement = $engagement;
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
                $this->engagement ? [
                    'is_liked_by_viewer' => $this->engagement->isLikedByViewer,
                    'likes_count' => $this->engagement->likesCount,
                    'views_count' => $this->engagement->viewsCount,
                    'downloads_count' => $this->engagement->downloadsCount,
                ] : null,
                'kanjis' => KanjiResource::collection($this->kanjis),
                'words' => $this->words,
                'processing_status' => $this->lastOperation ? [
                    'id' => $this->lastOperation->id,
                    'type' => $this->lastOperation->task_type,
                    'status' => $this->lastOperation->status->value,
                    'metadata' => $this->lastOperation->metadata,
                    'created_at' => $this->lastOperation->created_at?->toIso8601String(),
                    'updated_at' => $this->lastOperation->updated_at?->toIso8601String(),
                ] : null,
            ],
        ];
    }
}
