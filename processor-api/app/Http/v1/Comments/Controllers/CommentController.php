<?php

namespace App\Http\v1\Comments\Controllers;
use App\Application\Comments\Services\CommentService;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService
    ) {}


    public function entityComments(string $uuid, Request $request, CommentService $commentService): JsonResponse
    {
        $articleUid = EntityId::fromString($uuid);
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 10);

        $comments = $commentService->getArticleComments($articleUid, $page, $perPage);

        return response()->json([
            'success' => true,
            'comments' => $comments
        ], 200);
    }

    public function articleComments(string $uuid, Request $request, CommentService $commentService): JsonResponse
    {
        $articleUid = EntityId::fromString($uuid);
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(1, (int) $request->get('per_page', 10)));

        $comments = $commentService->getArticleComments($articleUid, $page, $perPage);

         return response()->json([
            'success' => true,
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'message' => 'Article comments retrieved successfully'
        ], 200);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
