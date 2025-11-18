<?php

namespace App\Application\Users\Services;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

interface UserServiceInterface
{
    /**
     * Get user profile by UUID.
     *
     * @param EntityId $userUuid User public UUID
     * @return Result Success data: DomainUser, Failure data: Error
     */
    public function getUserProfile(EntityId $userUuid): Result;
}
