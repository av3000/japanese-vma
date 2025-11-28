<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Auth\Interfaces\Services\AuthSessionServiceInterface;
use App\Domain\Users\Errors\UserErrors;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Log;

final class LogoutUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuthSessionServiceInterface $authSession
    ) {}

    /**
     * Logout current authenticated user
     *
     * @return Result Success: null, Failure: Error
     */
    public function execute(): Result
    {
        try {
            if (!$this->authSession->isAuthenticated()) {
                return Result::failure(UserErrors::notAuthenticated());
            }

            $userId = $this->authSession->getUserId();
            $tokenId = $this->authSession->getTokenId();

            if (!$userId || !$tokenId) {
                return Result::failure(UserErrors::logoutFailed());
            }

            // Revoke token
            $this->userRepository->revokeToken($userId, $tokenId);

            // Clear session
            $this->authSession->clear();

            return Result::success(null);
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'error' => $e->getMessage(),
            ]);

            return Result::failure(UserErrors::logoutFailed());
        }
    }
}
