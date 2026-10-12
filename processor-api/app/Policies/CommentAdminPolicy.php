<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Comment;
use App\Infrastructure\Persistence\Models\User;

/**
 * Panel access to comments across every entity type. Moderation is deletion only: comment text is
 * never edited from the panel, even though `comments.update` exists in the catalogue.
 */
class CommentAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('comments.view');
    }

    public function view(User $user, Comment $comment): bool
    {
        return $user->hasPanelPermission('comments.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Comment $comment): bool
    {
        return false;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->hasPanelPermission('comments.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
