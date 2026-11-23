<?php

declare(strict_types=1);

namespace App\Http\v1\Comments\Resources;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the current reader may do with this comment, and what they have already
 * done to it.
 *
 * These are affordances, not enforcement - the write endpoints ask the same
 * `CommentPolicy` again. The point of shipping them is that a client no longer
 * has to re-derive the rules: the previous frontend reimplemented
 * `canDelete` in TypeScript and got the admin branch wrong, so admin delete
 * silently never worked.
 *
 * Whether the thread accepts *new* comments is deliberately absent: that is a
 * property of the parent entity (a locked Post), not of any one comment, and it
 * already travels on the parent's own resource.
 */
class CommentViewerResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        private readonly Comment $comment,
        private readonly ?AuthenticatedUser $viewer,
        private readonly CommentPolicy $policy,
    ) {
        parent::__construct($comment);
    }

    /**
     * @return array{is_liked: bool, can_edit: bool, can_delete: bool}
     */
    public function toArray(Request $request): array
    {
        return [
            'is_liked' => $this->comment->isLikedByViewer(),
            'can_edit' => $this->policy->canUpdate($this->viewer, $this->comment),
            'can_delete' => $this->policy->canDelete($this->viewer, $this->comment),
        ];
    }
}
