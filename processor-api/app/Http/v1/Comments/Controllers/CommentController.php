<?php

namespace App\Http\v1\Comments\Controllers;
use App\Application\Comments\Services\CommentService;
use App\Http\v1\Comments\Resources\CommentResource;
use App\Application\Articles\Services\ArticleService;
use App\Http\v1\Comments\Requests\IndexCommentRequest;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\DTOs\IncludeEngagementOptionsDTO;

use App\Domain\Comments\DTOs\CommentListDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        // TODO: use interfaces
        private CommentService $commentService,
        private ArticleService $articleService
    ) {}

    public function getArticleComments(IndexCommentRequest $request, string $uuid): JsonResponse
    {
        $entityId = $this->articleService->getArticleIdByUuid($uuid);
        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::ARTICLE);
    }

    private function getCommentsForEntity(IndexCommentRequest $request, int $entityId,
        ObjectTemplateType $entityType): JsonResponse
    {
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $paginatedComments = $this->commentService->getCommentsList(
            $listDTO,
            $entityType,
            $entityId,
            $request->user()
        );

        $likesMap = [];
        if ($listDTO->include_likes) {
            $includeOptions = new IncludeEngagementOptionsDTO(include_likes: true);
            $likesMap = $this->engagementService->getLikesCount(
                entitiesList: $paginatedComments->getItems(),
                entityId: $entityId,
                dto: $includeOptions
            );
        }

        $resources = [];
        foreach($paginatedComments->getItems() as $comment) {
            $resources[] = new CommentResource(
                comment: $comment,
                include_likes: $listDTO->include_likes,
                include_replies: $listDTO->include_replies,
                likes_count: $likesMap[$comment->getIdValue()] ?? 0
            );
        }

        $data = [
            'items' => $resources,
            'pagination' => [
                'page' => $paginatedComments->getPaginator()->currentPage(),
                'per_page' => $paginatedComments->getPaginator()->perPage(),
                'total' => $paginatedComments->getPaginator()->total(),
                'last_page' => $paginatedComments->getPaginator()->lastPage(),
                'has_more' => $paginatedComments->getPaginator()->hasMorePages(),
            ],
        ];

        return new JsonResponse($data, 200, []);
    }

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
