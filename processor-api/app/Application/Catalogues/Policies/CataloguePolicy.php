<?php

namespace App\Application\Catalogues\Policies;

use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Catalogues\Models\Catalogue;
use App\Domain\Shared\Enums\PublicityStatus;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Infrastructure\Persistence\Models\User;

class CataloguePolicy
{
    public function __construct(
        private readonly RoleServiceInterface $roleService
    ) {}

    public function canView(?User $user, Catalogue $catalogue): bool
    {
        if ($catalogue->getPublicity() === PublicityStatus::PUBLIC) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($this->roleService->isAdmin(new EntityId($user->uuid))) {
            return true;
        }

        return $user->id === $catalogue->getOwnerId()->value();
    }
}
