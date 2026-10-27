<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * The activity log is append-only: readable with `activity.view`, never writable from the panel.
 */
class AdminActivityPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::ActivityView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return null;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return null;
    }
}
