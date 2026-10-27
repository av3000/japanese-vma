<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth\Providers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\ActorPermissionCheckerInterface;
use App\Application\Users\Support\PanelPermission;
use App\Infrastructure\Persistence\Models\User;

/**
 * Reads the actor's roles and permissions through the same `User::hasPanelPermission()` the panel
 * policies use, so the application layer and the panel never disagree about who may act.
 */
final class SpatieActorPermissionChecker implements ActorPermissionCheckerInterface
{
    public function allows(AuthenticatedUser $actor, PanelPermission $permission): bool
    {
        return User::query()->find($actor->id->value())?->hasPanelPermission($permission) === true;
    }
}
