<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Models\ArticleStats;
use App\Http\v1\Engagement\Resources\EngagementStatsSummaryResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use App\Infrastructure\Persistence\Models\LastOperationState;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Article $resource
 */
class ArticleResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @var array{include_hashtags?: bool, include_stats?: bool}|null
     */
    private ?array $options;

    private ?ArticleStats $stats;

    /**
     * @var array<int, array{id: int|string, content: string, created_at?: mixed, updated_at?: mixed}|object>
     */
    private array $hashtags;

    public function __construct(
        Article $article,
        ?array $options = null,
        ?ArticleStats $stats = null,
        array $hashtags = [],
        private ?LastOperationState $lastOperation = null
    ) {
        parent::__construct($article);
        $this->options = $options;
        $this->stats = $stats;
        $this->hashtags = $hashtags;
    }

    /**
     * Transform the article domain model into an API representation.
     *
     * @return array{
     *     id: int,
     *     uuid: string,
     *     entity_type_uid: string,
     *     title_jp: string,
     *     title_en: ?string,
     *     content_preview_jp: string,
     *     content_preview_en: ?string,
     *     source_link: string,
     *     publicity: int,
     *     status: int,
     *     jlpt_levels: array{n1: int, n2: int, n3: int, n4: int, n5: int, uncommon: int},
     *     author: AuthorResource,
     *     hashtags: array<int, HashtagResource>,
     *     created_at: string,
     *     updated_at: string,
     *     engagement: EngagementStatsSummaryResource,
     *     kanjis: array<int, KanjiResource>,
     *     processing_status: ProcessingStatusResource|null
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Article $article */
        $article = $this->resource;
        $publicity = (int) $article->getPublicity()->value;
        $status = (int) $article->getStatus()->value;
        $jlptLevels = [
            'n1' => (int) $article->getJlptLevels()->n1,
            'n2' => (int) $article->getJlptLevels()->n2,
            'n3' => (int) $article->getJlptLevels()->n3,
            'n4' => (int) $article->getJlptLevels()->n4,
            'n5' => (int) $article->getJlptLevels()->n5,
            'uncommon' => (int) $article->getJlptLevels()->uncommon,
        ];

        $includeHashtags = $this->options['include_hashtags'] ?? true;
        $includeStats = $this->options['include_stats'] ?? true;

        return [
            'id' => (int) $article->getIdValue(),
            'uuid' => (string) $article->getUid(),
            'entity_type_uid' => (string) $article->getEntityTypeUid(),
            'title_jp' => (string) $article->getTitleJp(),
            'title_en' => $article->getTitleEn()?->value,
            'content_preview_jp' => $article->getContentJp()->excerpt(),
            'content_preview_en' => $article->getContentEn()?->excerpt(),
            'source_link' => (string) $article->getSourceUrl(),
            'publicity' => $publicity,
            'status' => $status,
            'jlpt_levels' => $jlptLevels,
            'author' => new AuthorResource([
                'id' => $article->getAuthorId()->value(),
                'name' => $article->getAuthorName()->value(),
                'uuid' => $article->getAuthorUuid()->value(),
            ]),
            'hashtags' => HashtagResource::collection($includeHashtags ? $this->hashtags : []),
            'created_at' => $article->getCreatedAt()->format('c'),
            'updated_at' => $article->getUpdatedAt()->format('c'),
            'engagement' => new EngagementStatsSummaryResource($includeStats ? $this->stats : null),
            'kanjis' => KanjiResource::collection($article->getKanjis()),
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
}
