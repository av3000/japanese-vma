<?php

declare(strict_types=1);

namespace App\Http\v1\Engagement\Likes\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Engagement\Actions\ToggleLikeAction;
use App\Domain\Users\Errors\UserErrors;
use App\Http\Controllers\Controller;
use App\Http\v1\Engagement\Likes\Requests\LikeInstanceRequest;
use App\Http\v1\Engagement\Resources\LikeToggleResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class LikeController extends Controller
{
    public function __construct(
        private readonly ToggleLikeAction $toggleLike,
        private readonly CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * Toggle the authenticated user's like on an Article, Catalogue, Post, or Comment.
     *
     * Idempotent per direction: the response always states the resulting state rather
     * than the transition, so a repeated request cannot leave the caller guessing.
     *
     * @response LikeToggleResource
     */
    #[Response(type: 'LikeToggleResource')]
    public function likeInstance(LikeInstanceRequest $request): JsonResponse|JsonResource
    {
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

        if ($authenticatedUser === null) {
            return TypedResults::fromError(UserErrors::notAuthenticated());
        }

        $result = $this->toggleLike->execute($request->toDTO($authenticatedUser->id));

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return new LikeToggleResource($result->getData());
    }
}
