<?php

declare(strict_types=1);

namespace App\Http\v1\Comments\Resources;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\Models\Comment;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Http\v1\Shared\Resources\AuthorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A top-level comment, plus a bounded preview of its reply subtree.
 *
 * `replies_count` is the size of the whole subtree at every depth and is always
 * present; `replies` holds at most `replies_limit` of them, oldest first, and is
 * empty when the caller did not ask for previews. A comment with
 * `replies_count: 40` and `replies: []` is a normal response, not a truncation
 * bug - `GET /comments/{uuid}/replies` serves the rest.
 *
 * `entity_type_uuid` carries the ObjectTemplateType case rather than its title.
 * The create request already takes `entity_type` as an ObjectTemplateType uuid;
 * answering with the title made request and response describe one concept in
 * two vocabularies and generated two client enums for it. The enum instance is
 * returned so the schema references the shared type instead of a bare string.
 * `entity_type_label` is the human-facing counterpart and stays an open string -
 * pinning it as an enum would break clients whenever a template is added.
 *
 * @property-read Comment $resource
 */
class CommentResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        Comment $comment,
        private readonly ?AuthenticatedUser $viewer = null,
        private readonly ?CommentPolicy $policy = null,
    ) {
        parent::__construct($comment);
    }

    /**
     * @return array{
     *     id: int,
     *     uuid: string,
     *     entity_uuid: string|null,
     *     entity_type_uuid: string,
     *     entity_type_label: string,
     *     author: AuthorResource|null,
     *     content: string,
     *     parent_comment_id: int|null,
     *     is_reply: bool,
     *     likes_count: int,
     *     viewer: CommentViewerResource,
     *     replies_count: int,
     *     replies: array<int, CommentReplyResource>,
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $policy = $this->policy ?? new CommentPolicy;
        $authorUuid = $comment->getAuthorUuid();

        /** @var array<int, CommentReplyResource> $replies */
        $replies = array_map(
            fn (Comment $reply): CommentReplyResource => new CommentReplyResource($reply, $this->viewer, $policy),
            $comment->getReplies(),
        );

        return [
            'id' => $comment->getIdValue(),
            'uuid' => $comment->getUuid()->value(),
            'entity_uuid' => $comment->getEntityUuidValue(),
            /** @var ObjectTemplateType */
            'entity_type_uuid' => $comment->getEntityType(),
            /** @var string */
            'entity_type_label' => $comment->getEntityType()->label(),
            'author' => $authorUuid === null ? null : new AuthorResource([
                'id' => $comment->getAuthorId()->value(),
                'name' => (string) $comment->getAuthorName(),
                'uuid' => $authorUuid->value(),
            ]),
            'content' => $comment->getContent(),
            'parent_comment_id' => $comment->getParentCommentId(),
            'is_reply' => $comment->isReply(),
            'likes_count' => $comment->getLikesCount(),
            'viewer' => new CommentViewerResource($comment, $this->viewer, $policy),
            'replies_count' => $comment->getRepliesCount(),
            /** @var array<int, CommentReplyResource> */
            'replies' => $replies,
            'created_at' => $comment->getCreatedAt()->format('c'),
            'updated_at' => $comment->getUpdatedAt()->format('c'),
        ];
    }
}
