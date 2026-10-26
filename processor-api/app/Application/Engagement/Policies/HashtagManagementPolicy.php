<?php

declare(strict_types=1);

namespace App\Application\Engagement\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;

/**
 * Renaming and merging tags change every entity that uses them. Only MergeHashtagsAction and
 * RenameHashtagAction read this.
 */
class HashtagManagementPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canManage(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, 'hashtags.manage');
    }
}
