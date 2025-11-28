<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Domain\Users\DTOs\RegisterUserDTO;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\CustomLists\Interfaces\Repositories\CustomListRepositoryInterface;
use App\Domain\Users\DTOs\RegisteredUserDTO;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Users\Factories\UserFactory;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RegisterUserAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly CustomListRepositoryInterface $customListRepository
    ) {}

    /**
     * Register a new user with default setup
     *
     * @param RegisterUserDTO $dto Registration data
     * @return Result Success data: RegisteredUserDTO, Failure data: Error
     *
     */
    public function execute(RegisterUserDTO $dto): Result
    {
        $registeredUser = DB::transaction(function () use ($dto) {
            $userData = UserFactory::createFromDTO($dto);

            $result = $this->userRepository->create(
                uuid: $userData['uuid'],
                name: $userData['name'],
                email: $userData['email'],
                hashedPassword: $userData['hashedPassword']
            );

            $this->customListRepository->createDefaultListsForUser($result['userId']);

            $accessToken = $this->userRepository->generateAccessToken($result['userId']);

            $domainUser = $this->userRepository->findByUuid($result['uuid']);

            if (!$domainUser) {
                return Result::failure(UserErrors::notFound($result['uuid']->value()));
            }

            return new RegisteredUserDTO(
                user: $domainUser,
                accessToken: $accessToken
            );
        });

        return Result::success($registeredUser);
    }
}
