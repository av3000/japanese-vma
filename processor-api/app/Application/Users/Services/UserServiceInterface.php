<?php

namespace App\Application\Users\Services;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Shared\Results\Result;
use App\Domain\Users\Models\User;

interface UserServiceInterface
{
    /**
     * Get user profile by UUID.
     *
     * @param EntityId $userUuid User public UUID
     * @return Result Success data: DomainUser, Failure data: Error
     */
    public function findByUuid(EntityId $userUuid): Result;

    /**
     * Finds users based on the given criteria.
     *
     * @param UserQueryCriteria|null $criteria Optional criteria for filtering.
     * @return User[]
     */
    public function findUsers(?UserQueryCriteria $criteria = null): array;
}
