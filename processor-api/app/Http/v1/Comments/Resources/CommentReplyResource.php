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
 * A reply: every field of a top-level comment except `replies` and
 * `replies_count`.
 *
 * Split from CommentResource on purpose. Storage allows unlimited nesting, but
 * the contract does not expose a tree: a reply to a reply is returned in the
 * same flat, chronological subtree as its siblings, carrying
 * `parent_comment_id` so a client can render "replying to ...". That keeps the
 * response bounded, keeps `replies_count` unambiguous, and keeps the generated
 * TypeScript free of a self-referential type.
 *
 * The field list is written out rather than shared with CommentResource:
 * Scramble reads the literal array to build the component schema, and a helper
 * call would document both endpoints as returning `mixed`.
 *
 * @property-read Comment $resource
 */
class CommentReplyResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        Comment $comment,
        private readonly ?AuthenticatedUser $viewer,
        private readonly CommentPolicy $policy,
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
     *     created_at: string,
     *     updated_at: string
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $authorUuid = $comment->getAuthorUuid();

        return [
            'id' => $comment->getIdValue(),
            'uuid' => $comment->getUuid()->value(),
            'entity_uuid' => $comment->getEntityUuidValue(),
            /** @var ObjectTemplateType */
            'entity_type_uuid' => $comment->getEntityType(),
            /** @var string */
            'entity_type_label' => $comment->getEntityType()->label(),
            // Null when the author row is gone. Better than a name-shaped hole:
            // the client renders a placeholder and hides owner affordances,
            // which `viewer.can_edit` already reports as false.
            'author' => $authorUuid === null ? null : new AuthorResource([
                'id' => $comment->getAuthorId()->value(),
                'name' => (string) $comment->getAuthorName(),
                'uuid' => $authorUuid->value(),
            ]),
            'content' => $comment->getContent(),
            'parent_comment_id' => $comment->getParentCommentId(),
            'is_reply' => $comment->isReply(),
            'likes_count' => $comment->getLikesCount(),
            'viewer' => new CommentViewerResource($comment, $this->viewer, $this->policy),
            'created_at' => $comment->getCreatedAt()->format('c'),
            'updated_at' => $comment->getUpdatedAt()->format('c'),
        ];
    }
}
