<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Domain\Users\Errors\UserErrors;
use App\Shared\Results\Result;

final class GetCurrentUserAction
{
    public function __construct(
        private readonly AuthSessionServiceInterface $authSession
    ) {}

    /**
     * Get currently authenticated user
     *
     * @return Result Success data: DomainUser, Failure data: Error
     */
    public function execute(): Result
    {
        $domainUser = $this->authSession->getAuthenticatedDomainUser();

        if (!$domainUser) {
            // This implicitly covers both notAuthenticated and notFound scenarios for the current user.
            return Result::failure(UserErrors::notAuthenticated());
        }

        return Result::success($domainUser);
    }
}
