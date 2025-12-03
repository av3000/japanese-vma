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
use App\Domain\Users\Queries\UserQueryCriteria;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends Controller
{
    public function __construct(
        private UserServiceInterface $userService,
        private UserViewPolicy $userViewPolicy
    ) {}

    /**
     * Get a list of publicly visible users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 10);
        $offset = $request->query('offset', 0);

        $criteria = UserQueryCriteria::forPublicListing($limit, $offset);
        $users = $this->userService->findUsers($criteria);

        return response()->json([
            'data' => UserProfileResource::collection($users),
        ]);
    }

    /**
     * Display user profile.
     *
     * @param string $uuid User UUID
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        $userUuid = EntityId::from($uuid);

        // TODO: I think AuthSession service should be used here to access authorized user, not sure how userViewPolicy integrates with it, I might introduced them both for the same goal mistakenly
        $isOwnProfile = $this->userViewPolicy->isOwnProfile(auth('api')->user(), $userUuid);

        $result = $this->userService->findByUuid($userUuid);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $user = $result->getData();

        if (!$this->userViewPolicy->view(auth('api')->user(), $user)) {
            TypedResults::fromError(UserErrors::notAuthorized());
        }

        return TypedResults::ok(
            new UserProfileResource(user: $user, isOwnProfile: $isOwnProfile)
        );
    }
}
