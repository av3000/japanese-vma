<?php

namespace App\Http\v1\Comments\Resources;

use App\Domain\Comments\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Comment $resource
 */
class CommentResource extends JsonResource
{
    public static $wrap = null;

    private bool $include_replies;

    private array $replies;

    public function __construct(
        Comment $comment,
        bool $include_replies = false,
        array $replies = []
    ) {
        parent::__construct($comment);
        $this->include_replies = $include_replies;
        $this->replies = $replies;
    }

    /**
     * @return array{
     *     id: int,
     *     entity_uuid: string,
     *     entity_type: string,
     *     author_name: string,
     *     author_id: int,
     *     content: string,
     *     parent_comment_id: int|null,
     *     is_reply: bool,
     *     created_at: string,
     *     updated_at: string,
     *     likes_count: int,
     *     is_liked_by_viewer: bool,
     *     replies: array<int, CommentResource>
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $data = [
            'id' => (int) $comment->getIdValue(),
            'entity_uuid' => $comment->getEntityUuid()->value(),
            'entity_type' => $comment->getEntityType(),
            'author_name' => $comment->getAuthorName(),
            'author_id' => (int) $comment->getAuthorId()->value(),
            'content' => $comment->getContent(),
            'parent_comment_id' => $comment->getParentCommentId(),
            'is_reply' => (bool) $comment->isReply(),
            'created_at' => $comment->getCreatedAt()->format('c'),
            'updated_at' => $comment->getUpdatedAt()->format('c'),
            'likes_count' => (int) $comment->getLikesCount(),
            'is_liked_by_viewer' => (bool) $comment->isLikedByViewer(),
            'replies' => [],
        ];

        if ($this->include_replies && ! $comment->isReply()) {
            $data['replies'] = CommentResource::collection($this->replies);
        }

        return $data;
    }
}
