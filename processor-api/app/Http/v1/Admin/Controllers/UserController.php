<?php

declare(strict_types=1);

namespace App\Http\v1\Admin\Controllers;

use App\Application\Users\Actions\GetCurrentUserAction;
use App\Http\Controllers\Controller;
use App\Application\Users\Services\UserServiceInterface;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Http\V1\Admin\Requests\UserIndexRequest;
use App\Http\v1\Users\Builders\UserResponseBuilder;
use Illuminate\Http\JsonResponse;
use App\Shared\Http\TypedResults;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly UserResponseBuilder $userResponseBuilder,
        private readonly GetCurrentUserAction $getCurrentUserAction
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

        $criteria = UserQueryCriteria::forAdminListing(
            uuid: $validatedData['uuid'] ?? null,
            name: $validatedData['name'] ?? null,
            email: $validatedData['email'] ?? null,
            role: $validatedData['role'] ?? null,
            includeInactive: $validatedData['include_inactive'] ?? false,
            limit: $validatedData['limit'] ?? 20,
            offset: $validatedData['offset'] ?? 0,
        );

        $authenticatedUserResult = $this->getCurrentUserAction->execute();
        $authenticatedUser = null;
        if ($authenticatedUserResult->isSuccess()) {
            $authenticatedUser = $authenticatedUserResult->getData();
        }

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
