<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Panel access to comments across every entity type. Moderation is deletion only: comment text is
 * never edited from the panel.
 */
class CommentAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::CommentsView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return null;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return PanelPermission::CommentsDelete;
    }
}
