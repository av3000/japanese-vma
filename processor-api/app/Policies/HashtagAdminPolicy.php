<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Uniquehashtag;
use App\Infrastructure\Persistence\Models\User;

/**
 * Tag clean-up is panel-only, so `hashtags.manage` is the whole rule. Tags are created by tagging
 * content and are only ever removed by merging them into another tag.
 */
class HashtagAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('hashtags.manage');
    }

    public function view(User $user, Uniquehashtag $hashtag): bool
    {
        return $user->hasPanelPermission('hashtags.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Uniquehashtag $hashtag): bool
    {
        return $user->hasPanelPermission('hashtags.manage');
    }

    public function delete(User $user, Uniquehashtag $hashtag): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
