<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Users\Services\UserServiceInterface;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Http\Controllers\Controller;
use App\Http\v1\Admin\Requests\UserIndexRequest;
use App\Http\v1\Users\Builders\UserResponseBuilder;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly UserResponseBuilder $userResponseBuilder,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Get a list of users for administration.
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $criteria = UserQueryCriteria::forAdminListing(
            uuid: $validatedData['uuid'] ?? null,
            name: $validatedData['name'] ?? null,
            email: $validatedData['email'] ?? null,
            role: $validatedData['role'] ?? null,
            includeInactive: $validatedData['include_inactive'] ?? false,
            limit: $validatedData['limit'] ?? 20,
            offset: $validatedData['offset'] ?? 0,
        );

        $paginatedUsersContextResult = $this->userService->find(
            $criteria,
            $this->currentUserProvider->currentAuthenticatedUser(),
        );

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
            'message' => 'deleted successfully',
        ]);
    }
}
