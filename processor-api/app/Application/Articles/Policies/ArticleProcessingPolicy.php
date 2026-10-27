<?php

declare(strict_types=1);

namespace App\Application\Articles\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Support\PanelPermission;

/**
 * Requeueing processing puts a job on the production queue, so it is an operations right, not an
 * article-moderation one. Only RequeueArticleProcessingAction reads this.
 */
class ArticleProcessingPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canRequeue(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, PanelPermission::OperationsManage);
    }
}
