<?php

namespace App\Http\v1\Comments\Controllers;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Comments\Services\CommentService;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use App\Http\v1\Comments\Requests\IndexCommentRequest;
use App\Http\v1\Comments\Requests\StoreCommentRequest;
use App\Http\v1\Comments\Resources\CommentResource;
use App\Http\v1\Concerns\ResolvesOptionalApiUser;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use ResolvesOptionalApiUser;

    public function __construct(
        // TODO: use interface for commentService
        private CommentService $commentService,
        private ArticleServiceInterface $articleService,
        private CatalogueServiceInterface $catalogueService,
        // private EngagementServiceInterface $engagementService
    ) {
    }

    public function getArticleComments(IndexCommentRequest $request, string $uuid): JsonResponse
    {
        $entityUuid = EntityId::from($uuid);
        $entityId = $this->articleService->getArticleIdByUuid($entityUuid);

        if ($entityId === null) {
            return TypedResults::notFound('Article not found');
        }

        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::ARTICLE);
    }

    public function getCatalogueComments(IndexCommentRequest $request, string $uuid): JsonResponse
    {
        $entityUuid = EntityId::from($uuid);

        $entityId = $this->catalogueService->getIdByUuid($entityUuid);

        if ($entityId === null) {
            return TypedResults::notFound('Catalogue not found');
        }

        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::LIST);
    }

    private function getCommentsForEntity(
        IndexCommentRequest $request,
        // TODO: after all legacy instances that reference 'id' will be migrated, use UUID.
        int $entityId,
        ObjectTemplateType $entityType
    ): JsonResponse {
        // TODO: Implement include_replies
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $paginatedComments = $this->commentService->getCommentsList(
            dto: $listDTO,
            entityType: $entityType,
            entityId: $entityId,
            viewerUserId: $this->resolveOptionalApiUserId($request)
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

        // TODO: consider returning resource instead of typed result
        return TypedResults::ok($data);
    }

    /**
     * @response array{success: true, data: array{id: int, entity_uuid: string, entity_type: string, author_name: string, author_id: int, content: string, parent_comment_id: int|null, is_reply: bool, created_at: string, updated_at: string, likes_count: int, is_liked_by_viewer: bool}}
     */
    #[Response(201, type: 'array{success: true, data: array{id: int, entity_uuid: string, entity_type: string, author_name: string, author_id: int, content: string, parent_comment_id: int|null, is_reply: bool, created_at: string, updated_at: string, likes_count: int, is_liked_by_viewer: bool}}')]
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $comment = $this->commentService->createCommentForEntity(
            dto: CommentCreateDTO::fromRequest($request->validated()),
            author: auth('api')->user(),
        );

        return TypedResults::created(new CommentResource($comment));
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
