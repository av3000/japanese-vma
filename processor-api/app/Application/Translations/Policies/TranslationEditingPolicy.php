<?php

declare(strict_types=1);

namespace App\Application\Translations\Policies;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Support\PanelPermission;

/**
 * Writing a translation changes what learners read, so it needs `translations.update`. Only
 * TranslationEditorService reads this: the panel hides the Save action, but this is the boundary.
 */
class TranslationEditingPolicy
{
    public function __construct(
        private readonly ActorPermissionCheckerInterface $permissions,
    ) {
    }

    public function canUpdate(AuthenticatedUser $actor): bool
    {
        return $this->permissions->allows($actor, PanelPermission::TranslationsUpdate);
    }
}
