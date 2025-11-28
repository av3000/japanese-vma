<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;

final class GetCurrentUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthSessionServiceInterface $authSession
    ) {}

    /**
     * Get currently authenticated user
     *
     * @return Result Success data: DomainUser, Failure data: Error
     */
    public function execute(): Result
    {
        if (!$this->authSession->isAuthenticated()) {
            return Result::failure(UserErrors::notAuthenticated());
        }

        $uuid = $this->authSession->getUserUuid();

        if (!$uuid) {
            return Result::failure(UserErrors::notAuthenticated());
        }

        $user = $this->userRepository->findByUuid(new EntityId($uuid));

        if (!$user) {
            return Result::failure(UserErrors::notFound($uuid));
        }

        return Result::success($user);
    }
}
