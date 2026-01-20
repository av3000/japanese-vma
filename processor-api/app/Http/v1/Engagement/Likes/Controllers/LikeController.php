<?php

namespace App\Http\v1\Engagement\Likes\Controllers;

use App\Application\Engagement\Services\EngagementServiceInterface;

use App\Http\Controllers\Controller;
use App\Http\v1\Engagement\Likes\Requests\LikeInstanceRequest;
use App\Http\v1\Engagement\Resources\LikeResource;
use App\Shared\Http\TypedResults;

class LikeController extends Controller
{
    public function __construct(
        // TODO: use interface for commentService
        private EngagementServiceInterface $engagementService
    ) {}

    // TODO: look at getCommentsForEntity in CommentController for managing the ObjectTypeId validation for consistency
    public function likeInstance(LikeInstanceRequest $request)
    {
        $like = $this->engagementService->toggleLike(
            auth('api')->user()->id,
            $request->get('real_object_id'),
            $request->getObjectType()
        );

        return TypedResults::ok([
            'success' => true,
            'like' => !!$like,
            'likeValues' => $like ? new LikeResource($like) : null
        ]);
    }
}
