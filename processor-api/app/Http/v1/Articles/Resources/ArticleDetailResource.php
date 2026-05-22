<?php

namespace App\Http\v1\Articles\Resources;

use App\Domain\Articles\DTOs\ArticleDetailResultDTO;
use App\Http\v1\Engagement\Resources\EngagementResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\JapaneseMaterial\Kanjis\Resources\KanjiResource;
use App\Http\v1\LastOperations\Resources\ProcessingStatusResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ArticleDetailResultDTO $resource
 */
class ArticleDetailResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(ArticleDetailResultDTO $detail)
    {
        parent::__construct($detail);
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
        /** @var ArticleDetailResultDTO $detail */
        $detail = $this->resource;
        $article = $detail->article;

        $publicity = (int) $article->getPublicity()->value;
        $status = (int) $article->getStatus()->value;
        // TODO: move to separate resource for entity agnostic usage
        $jlptLevels = [
            'n1' => (int) $article->getJlptLevels()->n1,
            'n2' => (int) $article->getJlptLevels()->n2,
            'n3' => (int) $article->getJlptLevels()->n3,
            'n4' => (int) $article->getJlptLevels()->n4,
            'n5' => (int) $article->getJlptLevels()->n5,
            'uncommon' => (int) $article->getJlptLevels()->uncommon,
        ];

        return [
            'id' => (int) $article->getIdValue(),
            'uid' => (string) $article->getUid(),
            'entity_type_uid' => (string) $article->getEntityTypeUid(),
            'title_jp' => $article->getTitleJp()->value,
            'title_en' => $article->getTitleEn()?->value,
            'content_jp' => $article->getContentJp()->value,
            'content_en' => $article->getContentEn()?->value,
            'source_link' => $article->getSourceUrl()->value,
            'publicity' => $publicity,
            'status' => $status,
            'jlpt_levels' => $jlptLevels,
            'author' => new AuthorResource([
                'id' => $article->getAuthorId()->value(),
                'name' => $article->getAuthorName()->value(),
                'uuid' => $article->getAuthorUuid()->value(),
            ]),
            'hashtags' => HashtagResource::collection($detail->hashtags),
            'created_at' => $article->getCreatedAt()->format('c'),
            'updated_at' => $article->getUpdatedAt()->format('c'),
            'engagement' => new EngagementResource($detail->engagement),
            'kanjis' => KanjiResource::collection($detail->kanjis),
            'words' => ArticleWordResource::collection($detail->words),
            'processing_status' => $detail->lastOperation ? new ProcessingStatusResource([
                'id' => $detail->lastOperation->id,
                'type' => $detail->lastOperation->task_type,
                'status' => $detail->lastOperation->status,
                'metadata' => $detail->lastOperation->metadata,
                'created_at' => $detail->lastOperation->created_at?->toIso8601String(),
                'updated_at' => $detail->lastOperation->updated_at?->toIso8601String(),
            ]) : null,
        ];
    }
}
