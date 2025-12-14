<?php

declare(strict_types=1);

namespace App\Http\v1\Auth\Controllers;

use App\Application\Users\Actions\GetCurrentUserAction;
use App\Application\Users\Actions\LoginUserAction;
use App\Application\Users\Actions\LogoutUserAction;
use App\Http\Controllers\Controller;
use App\Http\v1\Auth\Requests\RegisterRequest;
use App\Http\v1\Auth\Resources\AuthUserResource;
use App\Domain\Users\DTOs\RegisterUserDTO;
use App\Application\Users\Actions\RegisterUserAction;
use App\Domain\Users\DTOs\LoginUserDTO;
use App\Http\v1\Auth\Requests\LoginRequest;
use App\Shared\Http\TypedResults;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterUserAction $registerUserAction,
        private readonly LoginUserAction $loginUserAction,
        private readonly GetCurrentUserAction $getCurrentUserAction,
        private readonly LogoutUserAction $logoutUserAction,
    ) {}

    /**
     * POST /api/v1/register
     * Register new user and return access token
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterUserDTO::fromRequest($request->validated());

        $result = $this->registerUserAction->execute($dto);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $registeredUser = $result->getData();

        return TypedResults::created(
            (new AuthUserResource($registeredUser->user))
                ->withToken($registeredUser->accessToken)
        );
    }

    /**
     * POST /api/v1/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginUserDTO::fromRequest($request->validated());

        $result = $this->loginUserAction->execute($dto);

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        $loggedInUser = $result->getData();

        return TypedResults::ok(
            (new AuthUserResource($loggedInUser->user))
                ->withToken($loggedInUser->accessToken)
        );
    }

    /**
     * POST /api/v1/logout
     */
    public function logout(): JsonResponse
    {
        $result = $this->logoutUserAction->execute();

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::ok(null, 'Successfully logged out');
    }

    /**
     * GET /api/v1/me
     */
    public function me(): JsonResponse
    {
        $result = $this->getCurrentUserAction->execute();

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::ok(
            new AuthUserResource($result->getData())
        );
    }
}
