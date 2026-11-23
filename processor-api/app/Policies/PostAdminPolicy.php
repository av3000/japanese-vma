<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Panel access to community posts. Posts are written in the React client; the panel only locks,
 * unlocks and deletes them through PostModerationServiceInterface, which applies
 * PostModerationPolicy.
 */
class PostAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::PostsView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return PanelPermission::PostsModerate;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return PanelPermission::PostsModerate;
    }
}
