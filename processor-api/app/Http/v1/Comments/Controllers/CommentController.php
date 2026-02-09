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
use App\Shared\Http\TypedResults;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        // TODO: use interface for commentService
        private CommentService $commentService,
        private ArticleServiceInterface $articleService,
        // private EngagementServiceInterface $engagementService
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
        // TODO: Implement include_replies
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $paginatedComments = $this->commentService->getCommentsList(
            dto: $listDTO,
            entityType: $entityType,
            entityId: $entityId,
            userId: auth('api')->user()->id
        );


        $resources = [];
        foreach ($paginatedComments->getItems() as $comment) {
            $resources[] = new CommentResource(
                comment: $comment,
                include_replies: $listDTO->include_replies,
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

        return TypedResults::ok($data);
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
