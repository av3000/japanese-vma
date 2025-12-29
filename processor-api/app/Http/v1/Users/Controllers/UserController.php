<?php

declare(strict_types=1);

namespace App\Http\v1\Users\Controllers;

use App\Application\Users\Actions\GetCurrentUserAction;
use App\Http\Controllers\Controller;
use App\Application\Users\Services\UserServiceInterface;
use App\Application\Users\Policies\UserViewPolicy;
use App\Application\Users\Services\RoleServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\v1\Users\Resources\UserProfileResource;
use App\Shared\Http\TypedResults;
use App\Domain\Users\Errors\UserErrors;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Http\V1\Admin\Requests\UserIndexRequest;
use App\Http\v1\Users\Builders\UserResponseBuilder;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly UserViewPolicy $userViewPolicy,
        private readonly UserResponseBuilder $userResponseBuilder,
        private readonly GetCurrentUserAction $getCurrentUserAction
    ) {}

    /**
     * Get a list of publicly visible users.
     *
     * @param UserIndexRequest $request Custom validation request
     * @return JsonResponse
     */
    public function index(UserIndexRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        $criteria = UserQueryCriteria::forPublicListing(
            page: $validatedData['page'],
            perPage: $validatedData['per_page'],
            sort: $validatedData['sort'],
            offset: $validatedData['offset'],
            limit: $validatedData['limit']
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
        $authenticatedUser = auth('api')->user();

        $userResult = $this->userService->findByUuid($userUuid, $authenticatedUser);

        if ($userResult->isFailure()) {
            return TypedResults::fromError($userResult->getError());
        }

        /** @var \App\Application\Users\DTOs\UserWithProfileContext $userContext */
        $userContext = $userResult->getData();
        $user = $userContext->user;

        if (!$this->userViewPolicy->view($authenticatedUser, $user)) {
            return TypedResults::fromError(UserErrors::notAuthorized());
        }

        return TypedResults::ok(
            // TODO: expand on userResponseBuilder for building single user response
            new UserProfileResource($userContext)
        );
    }
}
