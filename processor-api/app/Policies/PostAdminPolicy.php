<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Post;
use App\Infrastructure\Persistence\Models\User;

/**
 * Panel access to community posts. Posts are written in the React client; the panel only locks,
 * unlocks and deletes them through PostModerationServiceInterface, which applies
 * PostModerationPolicy.
 */
class PostAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('posts.view');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasPanelPermission('posts.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->hasPanelPermission('posts.moderate');
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->hasPanelPermission('posts.moderate');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
