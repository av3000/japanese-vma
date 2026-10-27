<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Catalogue;
use App\Infrastructure\Persistence\Models\User;

/**
 * Panel access to catalogues. Titles, descriptions and items are edited only by their owners in
 * the React client; the panel changes publicity and deletes through
 * CatalogueModerationServiceInterface, which applies CatalogueModerationPolicy.
 */
class CatalogueAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('catalogues.view');
    }

    public function view(User $user, Catalogue $catalogue): bool
    {
        return $user->hasPanelPermission('catalogues.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Catalogue $catalogue): bool
    {
        return $user->hasPanelPermission('catalogues.update');
    }

    public function delete(User $user, Catalogue $catalogue): bool
    {
        return $user->hasPanelPermission('catalogues.delete');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
