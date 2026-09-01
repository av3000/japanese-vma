<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Domain\Users\Errors\UserErrors;
use App\Shared\Results\Result;

final class GetCurrentUserAction
{
    public function __construct(
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Get currently authenticated user
     *
     * @return Result Success data: DomainUser, Failure data: ResultError
     */
    public function execute(): Result
    {
        $user = $this->currentUserProvider->currentUser();

        if ($user === null) {
            // This implicitly covers both notAuthenticated and notFound scenarios for the current user.
            return Result::failure(UserErrors::notAuthenticated());
        }

        return Result::success($user);
    }
}
