<?php
namespace App\Http\v1\Comments\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Domain\Comments\Models\Comment;

class CommentResource extends JsonResource
{
    private bool $include_likes;
    private bool $include_replies;
    private ?int $likes_count;
    private array $replies;

    public function __construct(
        Comment $comment,
        bool $include_likes = true,
        bool $include_replies = false,
        ?int $likes_count = null,
        array $replies = []
    ) {
        parent::__construct($comment);
        $this->include_likes = $include_likes;
        $this->include_replies = $include_replies;
        $this->likes_count = $likes_count;
        $this->replies = $replies;
    }

    public function toArray($request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;

        $data = [
            'id' => $comment->getIdValue(),
            'entity_id' => $comment->getEntityId()->value(),
            'entity_type' => $comment->getEntityType(),
            'author_id' => $comment->getAuthorId()->value(),
            'content' => $comment->getContent(),
            'parent_comment_id' => $comment->getParentCommentId()?->value(),
            'is_reply' => $comment->isReply(),
            'created_at' => $comment->getCreatedAt()->format('c'),
            'updated_at' => $comment->getUpdatedAt()->format('c'),
        ];

        if ($this->include_likes) {
            $data['likes_count'] = $this->likes_count ?? 0;
        }

        if ($this->include_replies && !$comment->isReply()) {
            $data['replies'] = $this->replies;
        }

        return $data;
    }
}
