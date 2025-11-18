<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Shared\Results\Result;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Get user profile by UUID.
     *
     * @param EntityId $userUuid User public UUID
     * @return Result Success data: DomainUser, Failure data: Error
     */
    public function getUserProfile(EntityId $userUuid): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound());
        }

        return Result::success($user);
    }
}
