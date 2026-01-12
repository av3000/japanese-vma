<?php

namespace App\Http\v1\Comments\Controllers;

use App\Application\Comments\Services\CommentService;
use App\Http\v1\Comments\Resources\CommentResource;
use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Http\v1\Comments\Requests\IndexCommentRequest;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\Enums\ObjectTemplateType;

use App\Domain\Comments\DTOs\CommentListDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        // TODO: use interface for commentService
        private CommentService $commentService,
        private ArticleServiceInterface $articleService,
        private EngagementServiceInterface $engagementService

    ) {}

    public function getArticleComments(IndexCommentRequest $request, string $uuid): JsonResponse
    {
        $entityUuid = new EntityId($uuid);
        $entityId = $this->articleService->getArticleIdByUuid($entityUuid);
        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::ARTICLE);
    }

    private function getCommentsForEntity(
        IndexCommentRequest $request,
        int $entityId,
        ObjectTemplateType $entityType
    ): JsonResponse {
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $paginatedComments = $this->commentService->getCommentsList(
            dto: $listDTO,
            entityType: $entityType,
            entityId: $entityId,
        );

        if ($listDTO->include_likes) {
            $likesMap = $this->engagementService->getLikesForList(entitiesList: $paginatedComments->getItems());
        }

        $resources = [];
        foreach ($paginatedComments->getItems() as $comment) {
            $resources[] = new CommentResource(
                comment: $comment,
                include_likes: $listDTO->include_likes,
                include_replies: $listDTO->include_replies,
                likes: $likesMap[$comment->getIdValue()] ?? []
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
        $articleUid = EntityId::from($uuid);
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
        $articleUid = EntityId::from($uuid);
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(1, (int) $request->get('per_page', 10)));

        $comments = $commentService->getArticleComments($articleUid, $page, $perPage);

        return response()->json([
            'success' => true,
            'data' => $comments['data'],
            'pagination' => $comments['pagination'],
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
