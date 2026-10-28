<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Panel access to articles. Authoring stays in the React client, so there is no create. Status,
 * publicity and delete go through ArticleModerationServiceInterface, which applies
 * ArticleModerationPolicy.
 */
class ArticleAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::ArticlesView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return PanelPermission::ArticlesUpdate;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return PanelPermission::ArticlesDelete;
    }
}
