<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;

/**
 * Panel access to catalogues. Titles, descriptions and items are edited only by their owners in
 * the React client; the panel changes publicity and deletes through
 * CatalogueModerationServiceInterface, which applies CatalogueModerationPolicy.
 */
class CatalogueAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::CataloguesView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return PanelPermission::CataloguesUpdate;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return PanelPermission::CataloguesDelete;
    }
}
