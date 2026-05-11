<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\Article;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Http\v1\Engagement\Resources\EngagementResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Article $resource
 */
class ArticleDetailResource extends JsonResource
{
    public static $wrap = null;

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
     * @return array{
     *     id: int,
     *     uid: string,
     *     entity_type_uid: string,
     *     title_jp: string,
     *     title_en: ?string,
     *     content_jp: string,
     *     content_en: ?string,
     *     source_link: string,
     *     publicity: int,
     *     status: int,
     *     jlpt_levels: array{n1: int, n2: int, n3: int, n4: int, n5: int, uncommon: int},
     *     author: AuthorResource,
     *     hashtags: array<int, HashtagResource>,
     *     created_at: string,
     *     updated_at: string,
     *     engagement: EngagementResource|null,
     *     kanjis: array<int, KanjiResource>,
     *     words: array<int, mixed>,
     *     processing_status: ProcessingStatusResource|null
     * }
     */
    public function toArray(Request $request): array
    {
        $publicity = (int) $this->getPublicity()->value;
        $status = (int) $this->getStatus()->value;
        // TODO: move to separate resource for entity agnostic usage
        $jlptLevels = [
            'n1' => (int) $this->getJlptLevels()->n1,
            'n2' => (int) $this->getJlptLevels()->n2,
            'n3' => (int) $this->getJlptLevels()->n3,
            'n4' => (int) $this->getJlptLevels()->n4,
            'n5' => (int) $this->getJlptLevels()->n5,
            'uncommon' => (int) $this->getJlptLevels()->uncommon,
        ];

        return [
            'id' => (int) $this->getIdValue(),
            'uid' => (string) $this->getUid(),
            'entity_type_uid' => (string) $this->getEntityTypeUid(),
            'title_jp' => $this->getTitleJp()->value,
            'title_en' => $this->getTitleEn()?->value,
            'content_jp' => $this->getContentJp()->value,
            'content_en' => $this->getContentEn()?->value,
            'source_link' => $this->getSourceUrl()->value,
            'publicity' => $publicity,
            'status' => $status,
            'jlpt_levels' => $jlptLevels,
            'author' => new AuthorResource([
                'id' => $this->getAuthorId()->value(),
                'name' => $this->getAuthorName()->value(),
                'uuid' => $this->getAuthorUuid()->value(),
            ]),
            'hashtags' => HashtagResource::collection($this->hashtags),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
            'engagement' => $this->engagement ? new EngagementResource($this->engagement) : null,
            'kanjis' => KanjiResource::collection($this->kanjis),
            'words' => $this->articleWords(),
            'processing_status' => $this->lastOperation ? new ProcessingStatusResource([
                'id' => $this->lastOperation->id,
                'type' => $this->lastOperation->task_type,
                'status' => $this->lastOperation->status,
                'metadata' => $this->lastOperation->metadata,
                'created_at' => $this->lastOperation->created_at?->toIso8601String(),
                'updated_at' => $this->lastOperation->updated_at?->toIso8601String(),
            ]) : null,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function articleWords(): array
    {
        return $this->words;
    }
}
