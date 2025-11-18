<?php

declare(strict_types=1);

namespace App\Http\v1\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Users\Services\UserServiceInterface;
use App\Application\Users\Policies\UserViewPolicy;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\v1\Users\Resources\UserProfileResource;
use App\Shared\Http\TypedResults;
use App\Domain\Users\Errors\UserErrors;

use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService,
        private UserViewPolicy $userViewPolicy
    ) {}

    /**
     * Display user profile.
     *
     * @param string $uuid User UUID
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $userUuid = EntityId::from($uuid);
        $result = $this->userService->getUserProfile($userUuid);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $user = $result->getData();

        // Policy check (future-proof for private profiles)
        if (!$this->userViewPolicy->view(auth('api')->user(), $user)) {
            TypedResults::fromError(UserErrors::unauthorized());
        }

        $isOwnProfile = $this->userViewPolicy->isOwnProfile(auth('api')->user(), $user);

        return TypedResults::ok(
            new UserProfileResource(user: $user, isOwnProfile: $isOwnProfile)
        );
    }
}
