<?php

namespace App\Http\v1\Comments\Controllers;

use App\Application\Articles\Services\ArticleServiceInterface;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Catalogues\Services\CatalogueServiceInterface;
use App\Application\Comments\Services\CommentService;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Users\Errors\UserErrors;
use App\Http\Controllers\Controller;
use App\Http\v1\Comments\Requests\IndexCommentRequest;
use App\Http\v1\Comments\Requests\StoreCommentRequest;
use App\Http\v1\Comments\Resources\CommentListResource;
use App\Http\v1\Comments\Resources\CommentResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CommentController extends Controller
{
    public function __construct(
        // TODO: use interface for commentService
        private CommentService $commentService,
        private ArticleServiceInterface $articleService,
        private CatalogueServiceInterface $catalogueService,
        private CurrentUserProviderInterface $currentUserProvider,
        // private EngagementServiceInterface $engagementService
    ) {
    }

    /**
     * @response CommentListResource
     */
    #[Response(type: 'CommentListResource')]
    public function getArticleComments(IndexCommentRequest $request, string $uuid): JsonResource
    {
        $entityUuid = EntityId::from($uuid);
        $entityId = $this->articleService->getArticleIdByUuid($entityUuid);

        if ($entityId === null) {
            throw new NotFoundHttpException('Article not found');
        }

        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::ARTICLE);
    }

    /**
     * @response CommentListResource
     */
    #[Response(type: 'CommentListResource')]
    public function getCatalogueComments(IndexCommentRequest $request, string $uuid): JsonResource
    {
        $entityUuid = EntityId::from($uuid);

        $entityId = $this->catalogueService->getIdByUuid($entityUuid);

        if ($entityId === null) {
            throw new NotFoundHttpException('Catalogue not found');
        }

        return $this->getCommentsForEntity($request, $entityId, ObjectTemplateType::LIST);
    }

    private function getCommentsForEntity(
        IndexCommentRequest $request,
        // TODO: after all legacy instances that reference 'id' will be migrated, use UUID.
        int $entityId,
        ObjectTemplateType $entityType
    ): JsonResource {
        // TODO: Implement include_replies
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $paginatedComments = $this->commentService->getCommentsList(
            dto: $listDTO,
            entityType: $entityType,
            entityId: $entityId,
            viewerUserId: $this->currentUserProvider->currentAuthenticatedUser()?->id->value(),
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

        return new CommentListResource($data);
    }

    /**
     * @response CommentResource
     */
    #[Response(201, type: 'CommentResource')]
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $authenticatedUser = $this->currentUserProvider->currentAuthenticatedUser();

        if ($authenticatedUser === null) {
            return TypedResults::fromError(UserErrors::notAuthenticated());
        }

        $comment = $this->commentService->createCommentForEntity(
            dto: CommentCreateDTO::fromRequest($request->validated()),
            authorId: $authenticatedUser->id,
        );

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
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
