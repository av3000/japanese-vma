<?php

declare(strict_types=1);

namespace App\Http\V1\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Users\Services\UserServiceInterface;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Http\V1\Admin\Requests\UserIndexRequest;
use App\Http\v1\Users\Builders\UserResponseBuilder;
use Illuminate\Http\JsonResponse;
use App\Shared\Http\TypedResults;
use App\Http\v1\Users\Resources\UserProfileResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly UserResponseBuilder $userResponseBuilder
    ) {}

    /**
     * Get a list of users for administration.
     *
     * @param UserIndexRequest $request
     * @return JsonResponse
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // TODO: I think AuthSession service should be used here to access authorized user, not sure how userViewPolicy integrates with it, I might introduced them both for the same goal mistakenly
        $authenticatedUser = auth('api')->user();

        $criteria = UserQueryCriteria::forAdminListing(
            uuid: $validatedData['uuid'] ?? null,
            name: $validatedData['name'] ?? null,
            email: $validatedData['email'] ?? null,
            role: $validatedData['role'] ?? null,
            includeInactive: $validatedData['include_inactive'] ?? false,
            limit: $validatedData['limit'] ?? 20,
            offset: $validatedData['offset'] ?? 0,
        );

        $paginatedUsersContextResult = $this->userService->find($criteria, $authenticatedUser);

        if ($paginatedUsersContextResult->isFailure()) {
            return TypedResults::fromError($paginatedUsersContextResult->getError());
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $paginatedUsersContext */
        $paginatedUsersContext = $paginatedUsersContextResult->getData();
        $usersCollectionResponse = $this->userResponseBuilder->buildCollectionResponse($paginatedUsersContext);

        return TypedResults::ok($usersCollectionResponse);
    }

    public function delete(Request $request): JsonResponse
    {
        $validatedData = $request->validated();

        return TypedResults::ok([
            'message' => 'deleted successfully'
        ]);
    }
}
