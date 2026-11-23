<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Processing rows are written only by the jobs and the sweeper. The panel reads them with
 * `operations.view`; a retry (`operations.manage`) resets a row through the requeue action rather
 * than editing it.
 */
class ProcessingStateAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::OperationsView;
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
