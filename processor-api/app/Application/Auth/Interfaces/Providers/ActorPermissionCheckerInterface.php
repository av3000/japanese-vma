<?php

declare(strict_types=1);

namespace App\Application\Auth\Interfaces\Providers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Users\Support\PanelPermission;

/**
 * Whether an actor holds a panel permission from `config/permission-catalog.php`, with the `admin`
 * role passing every check. Panel-only use cases ask this instead of `AuthenticatedUser::$isAdmin`,
 * so a custom role that holds only the permission can still act.
 */
interface ActorPermissionCheckerInterface
{
    public function allows(AuthenticatedUser $actor, PanelPermission $permission): bool;
}
