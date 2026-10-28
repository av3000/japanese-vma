<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Tag clean-up is panel-only, so `hashtags.manage` is the whole rule. Tags are created by tagging
 * content and are only ever removed by merging them into another tag.
 */
class HashtagAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::HashtagsManage;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return PanelPermission::HashtagsManage;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return null;
    }
}
