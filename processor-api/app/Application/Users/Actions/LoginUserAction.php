<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Domain\Users\DTOs\{LoginUserDTO, RegisteredUserDTO};
use App\Domain\Users\Errors\UserErrors;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class LoginUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    /**
     * Authenticate user and generate access token
     *
     * @param LoginUserDTO $dto Login credentials
     * @return Result Success data: RegisteredUserDTO, Failure data: Error
     */
    public function execute(LoginUserDTO $dto): Result
    {
        try {
            $authData = $this->userRepository->findByEmailForAuth($dto->email);

            if (!$authData) {
                return Result::failure(UserErrors::invalidCredentials());
            }

            if (!Hash::check($dto->password, $authData['passwordHash'])) {
                return Result::failure(UserErrors::invalidCredentials());
            }

            $domainUser = $authData['user'];

            $accessToken = $this->userRepository->generateAccessToken($domainUser->getId());

            return Result::success(new RegisteredUserDTO(
                user: $domainUser,
                accessToken: $accessToken
            ));
        } catch (\Exception $e) {
            Log::error('User login failed', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);

            return Result::failure(UserErrors::loginFailed());
        }
    }
}
