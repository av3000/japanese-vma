<?php

declare(strict_types=1);

namespace App\Application\Users\Services;

use App\Application\Users\DTOs\UserWithProfileContext;
use App\Shared\Results\Result;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Application\Users\Interfaces\Repositories\UserRepositoryInterface;
use App\Application\Users\Policies\UserViewPolicy;
use App\Application\Users\Services\UserServiceInterface;
use App\Domain\Users\Models\Users;
use App\Domain\Users\Models\User as DomainUser;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Users\Errors\RoleErrors;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        // TODO: create and use interface
        private UserViewPolicy $userViewPolicy,
        private RoleServiceInterface $roleService
    ) {}

    /**
     * Get user profile by UUID.
     *
     * @param EntityId $userUuid User public UUID
     * @return Result Success data: DomainUser, Failure data: Error
     */
    // TODO: add authenticatedUser type -> should come from authSession from controller.
    public function findByUuid(EntityId $userUuid,  $authenticatedUser = null): Result
    {
        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            return Result::failure(UserErrors::notFound($userUuid->value()));
        }

        $isOwnProfile = false;
        if ($authenticatedUser) {
            $isOwnProfile = $this->userViewPolicy->isOwnProfile(
                $authenticatedUser,
                $user->getUuid()
            );
        }

        return Result::success(UserWithProfileContext::fromDomainUser($user, $isOwnProfile));
    }

    /**
     * Finds users based on the given criteria.
     *
     * @param UserQueryCriteria|null $criteria Optional criteria for filtering.
     * @return Result<LengthAwarePaginator<UserWithProfileContext>>
     */
    public function find(?UserQueryCriteria $criteria = null, $authenticatedUser = null): Result
    {
        if ($criteria?->role !== null && !$this->roleService->roleExists($criteria->role)) {
            return Result::failure(RoleErrors::invalidRole($criteria->role));
        }

        /** @var Users $paginatedUsersCollection */
        $paginatedUsersCollection = $this->userRepository->find($criteria);

        // TODO: should be refactored to use some kind of mapper
        $paginator = $paginatedUsersCollection->getPaginator();
        $enrichedCollection = $paginator->getCollection()->map(
            function (DomainUser $user) use ($authenticatedUser): UserWithProfileContext {
                $isOwnProfile = false;
                if ($authenticatedUser) {
                    $isOwnProfile = $this->userViewPolicy->isOwnProfile(
                        $authenticatedUser,
                        $user->getUuid()
                    );
                }

                $isViewerAdmin = $authenticatedUser->isAdmin();
                return UserWithProfileContext::fromDomainUser($user, $isOwnProfile, $isViewerAdmin);
            }
        );

        $paginator->setCollection($enrichedCollection);

        return Result::success($paginator);
    }
}
