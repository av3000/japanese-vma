<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Domain\Users\Errors\UserErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;

final class LogoutUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Logout current authenticated user
     *
     * @return Result Success: null, Failure: ResultError
     */
    public function execute(): Result
    {
        try {
            $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

            if ($authenticatedUser === null) {
                return Result::failure(UserErrors::notAuthenticated());
            }

            $tokenId = $this->currentUserProvider->currentAccessTokenId();

            if ($tokenId === null) {
                return Result::failure(UserErrors::logoutFailed());
            }

            $this->userRepository->revokeToken($authenticatedUser->id, $tokenId);

            return Result::success(null);
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'error' => $e->getMessage(),
            ]);

            return Result::failure(UserErrors::logoutFailed());
        }
    }
}
