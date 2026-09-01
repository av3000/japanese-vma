<?php

namespace App\Http\v1\Engagement\Likes\Controllers;

use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Engagement\Services\EngagementServiceInterface;

use App\Http\Controllers\Controller;
use App\Http\v1\Engagement\Likes\Requests\LikeInstanceRequest;
use App\Http\v1\Engagement\Resources\LikeResource;
use App\Shared\Http\TypedResults;
use Illuminate\Auth\AuthenticationException;

class LikeController extends Controller
{
    public function __construct(
        // TODO: use interface for commentService
        private EngagementServiceInterface $engagementService,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    // TODO: look at getCommentsForEntity in CommentController for managing the ObjectTypeId validation for consistency
    public function likeInstance(LikeInstanceRequest $request)
    {
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;

        $like = $this->engagementService->toggleLike(
            $authenticatedUser->id->value(),
            $request->get('real_object_id'),
            $request->getObjectType()
        );

        return TypedResults::ok([
            'success' => true,
            'like' => (bool) $like,
            'likeValues' => $like ? new LikeResource($like) : null,
        ]);
    }
}
