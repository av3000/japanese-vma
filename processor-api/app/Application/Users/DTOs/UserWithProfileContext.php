<?php

declare(strict_types=1);

namespace App\Application\Users\DTOs;

use App\Domain\Users\Models\User as DomainUser;

/**
 * A DTO (Data Transfer Object) or Projection that combines a DomainUser
 * with context-specific information relevant for presentation.
 */
class UserWithProfileContext
{
    public function __construct(
        public readonly DomainUser $user,
        public readonly bool $isOwnProfile,
        public readonly ?bool $isViewerAdmin = null,
    ) {}

    public static function fromDomainUser(DomainUser $user, bool $isOwnProfile, ?bool $isViewerAdmin = null): self
    {
        return new self($user, $isOwnProfile, $isViewerAdmin);
    }
}
