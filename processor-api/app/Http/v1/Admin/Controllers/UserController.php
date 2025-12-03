<?php

declare(strict_types=1);

namespace App\Http\V1\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Users\Services\UserServiceInterface;
use App\Domain\Users\Queries\UserQueryCriteria;
use App\Http\V1\Admin\Requests\UserIndexRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Shared\Http\TypedResults;
use App\Http\v1\Users\Resources\UserProfileResource;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService
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
            name: $validatedData['name'] ?? null,
            email: $validatedData['email'] ?? null,
            role: $validatedData['role'] ?? null,
            includeInactive: $validatedData['include_inactive'] ?? false,
            limit: $validatedData['limit'] ?? 20,
            offset: $validatedData['offset'] ?? 0,
        );
        $users = $this->userService->findUsers($criteria);

        return TypedResults::ok([
            'users' => UserProfileResource::collection($users),
        ]);
    }
}
