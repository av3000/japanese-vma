<?php

declare(strict_types=1);

namespace App\Http\v1\Community\Posts\Resources;

use App\Domain\Community\Posts\Models\Post as DomainPost;
use App\Domain\Community\Posts\Models\PostStats;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Http\v1\Engagement\Resources\EngagementStatsSummaryResource;
use App\Http\v1\Engagement\Resources\HashtagResource;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * List item shape. Kept separate from PostDetailResource so the generated
 * OpenAPI schema stays honest: only the detail response carries `content`.
 *
 * @property-read DomainPost $resource
 */
class PostListItemResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @param array<int, object|array<string, mixed>> $hashtags
     */
    public function __construct(
        DomainPost $resource,
        private readonly ?PostStats $stats = null,
        private readonly array $hashtags = [],
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     entity_type_uuid: string,
     *     title: string,
     *     topic: int,
     *     topic_label: string,
     *     locked: bool,
     *     author: AuthorResource,
     *     hashtags: array<int, HashtagResource>,
     *     engagement: EngagementStatsSummaryResource,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var DomainPost $post */
        $post = $this->resource;

        /** @var array<int, HashtagResource> $hashtags */
        $hashtags = array_map(
            static fn (object|array $hashtag): HashtagResource => new HashtagResource($hashtag),
            array_values($this->hashtags),
        );

        return [
            'id' => (int) $post->getIdValue(),
            'uuid' => (string) $post->getUuid()->value(),
            // posts.entity_type_uuid is nullable and was never backfilled;
            // the canonical value comes from the enum.
            'entity_type_uuid' => (string) ObjectTemplateType::POST->value,
            'title' => (string) $post->getTitle(),
            'topic' => (int) $post->getTopic()->value,
            'topic_label' => (string) $post->getTopic()->label(),
            'locked' => (bool) $post->isLocked(),
            'author' => new AuthorResource([
                'id' => $post->getAuthorId(),
                'name' => $post->getAuthorName(),
                'uuid' => $post->getAuthorUuid()->value(),
            ]),
            /** @var array<int, HashtagResource> */
            'hashtags' => $hashtags,
            'engagement' => new EngagementStatsSummaryResource($this->stats),
            'created_at' => $post->getCreatedAt()->format('c'),
            'updated_at' => $post->getUpdatedAt()->format('c'),
        ];
    }
}
