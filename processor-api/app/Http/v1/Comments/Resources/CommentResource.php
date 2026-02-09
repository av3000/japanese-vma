<?php

namespace App\Http\v1\Comments\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Comments\Models\Comment;
use App\Http\v1\Engagement\Resources\LikeResource;

class CommentResource extends JsonResource
{
    private bool $include_likes;
    private bool $include_replies;
    private ?array $likes;
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

    public function toArray($request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $data = [
            'id' => $comment->getIdValue(),
            'entity_uuid' => $comment->getEntityUuid()->value(),
            'entity_type' => $comment->getEntityType(),
            'author_name' => $comment->getAuthorName(),
            'author_id' => $comment->getAuthorId()->value(),
            'content' => $comment->getContent(),
            'parent_comment_id' => $comment->getParentCommentId()?->value(),
            'is_reply' => $comment->isReply(),
            'created_at' => $comment->getCreatedAt()->format('c'),
            'updated_at' => $comment->getUpdatedAt()->format('c'),
            'likes_count' => $comment->getLikesCount(),
            'is_liked_by_viewer' => $comment->isLikedByViewer(),
        ];

        if ($this->include_replies && !$comment->isReply()) {
            $data['replies'] = $this->replies;
        }

        return $data;
    }
}
