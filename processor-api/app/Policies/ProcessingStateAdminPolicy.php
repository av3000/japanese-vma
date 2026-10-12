<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\ProcessingState;
use App\Infrastructure\Persistence\Models\User;

/**
 * Processing rows are written only by the jobs and the sweeper. The panel reads them with
 * `operations.view`; a retry (`operations.manage`) resets a row through the requeue action rather
 * than editing it.
 */
class ProcessingStateAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('operations.view');
    }

    public function view(User $user, ProcessingState $state): bool
    {
        return $user->hasPanelPermission('operations.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ProcessingState $state): bool
    {
        return false;
    }

    public function delete(User $user, ProcessingState $state): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
