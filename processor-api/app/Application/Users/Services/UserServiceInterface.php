<?php

namespace App\Application\Users\Services;

use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Shared\Results\Result;

interface UserServiceInterface
{
    /**
     * Get user profile by UUID.
     *
     * @param EntityId $userUuid User public UUID
     * @return Result<UserWithProfileContext> Success data: UserWithProfileContext, Failure data: Error
     */
    public function findByUuid(EntityId $userUuid): Result;

    /**
     * Finds users based on the given criteria.
     *
     * @param UserQueryCriteria|null $criteria Optional criteria for filtering.
     * @return Result<LengthAwarePaginator<UserWithProfileContext>>
     */
    public function find(?UserQueryCriteria $criteria = null): Result;
}
