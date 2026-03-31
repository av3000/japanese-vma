<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\Article;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Article $resource
 */
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
     * @return array
     */
    public function toArray(Request $request): array
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
                'author' => new ArticleAuthorResource([
                    'id' => $this->getAuthorId()->value(),
                    'name' => $this->getAuthorName()->value(),
                ]),
                'hashtags' => HashtagResource::collection($this->hashtags),
                'created_at' => $this->getCreatedAt()->format('c'),
                'updated_at' => $this->getUpdatedAt()->format('c'),
                'engagement' => $this->engagement ? new ArticleDetailEngagementResource($this->engagement) : null,
                'kanjis' => KanjiResource::collection($this->kanjis),
                'words' => $this->words,
                'processing_status' => $this->lastOperation ? new ProcessingStatusResource($this->lastOperation) : null,
            ],
        ];
    }
}
