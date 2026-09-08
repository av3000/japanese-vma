<?php

namespace App\Http\v1\Comments\Controllers;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Auth\Interfaces\Providers\CurrentUserProviderInterface;
use App\Application\Comments\Services\CommentServiceInterface;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Comments\DTOs\CommentUpdateDTO;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Http\Controllers\Controller;
use App\Http\v1\Comments\Requests\IndexCommentRequest;
use App\Http\v1\Comments\Requests\StoreCommentRequest;
use App\Http\v1\Comments\Requests\UpdateCommentRequest;
use App\Http\v1\Comments\Resources\CommentListResource;
use App\Http\v1\Comments\Resources\CommentResource;
use App\Shared\Http\TypedResults;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentController extends Controller
{
    public function __construct(
        private CommentServiceInterface $commentService,
        private CurrentUserProviderInterface $currentUserProvider,
    ) {
    }

    /**
     * @response CommentListResource
     */
    #[Response(type: 'CommentListResource')]
    #[Response(404, description: 'Article not found')]
    public function getArticleComments(IndexCommentRequest $request, string $uuid): JsonResponse|JsonResource
    {
        return $this->listForEntity($request, ObjectTemplateType::ARTICLE, $uuid);
    }

    /**
     * @response CommentListResource
     */
    #[Response(type: 'CommentListResource')]
    #[Response(404, description: 'Catalogue not found')]
    public function getCatalogueComments(IndexCommentRequest $request, string $uuid): JsonResponse|JsonResource
    {
        return $this->listForEntity($request, ObjectTemplateType::LIST, $uuid);
    }

    /**
     * @response CommentResource
     */
    #[Response(201, type: 'CommentResource')]
    #[Response(404, description: 'Commented entity not found')]
    public function store(StoreCommentRequest $request): JsonResponse|JsonResource
    {
        $result = $this->commentService->createComment(
            dto: CommentCreateDTO::fromRequest($request->validated()),
            author: $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var Comment $comment */
        $comment = $result->getData();

        return (new CommentResource($comment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * @response CommentResource
     */
    #[Response(type: 'CommentResource')]
    #[Response(403, description: 'Only the comment author may edit it')]
    #[Response(404, description: 'Comment not found')]
    public function update(UpdateCommentRequest $request, string $uuid): JsonResponse|JsonResource
    {
        $result = $this->commentService->updateComment(
            commentUuid: EntityId::from($uuid),
            dto: CommentUpdateDTO::fromRequest($request->validated()),
            actor: $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var Comment $comment */
        $comment = $result->getData();

        return new CommentResource($comment);
    }

    #[Response(204, description: 'Comment and every reply beneath it were deleted')]
    #[Response(403, description: 'Only the comment author or an admin may delete it')]
    #[Response(404, description: 'Comment not found')]
    public function destroy(string $uuid): JsonResponse
    {
        $result = $this->commentService->deleteComment(
            commentUuid: EntityId::from($uuid),
            actor: $this->requiredAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        return TypedResults::noContent();
    }

    private function listForEntity(
        IndexCommentRequest $request,
        ObjectTemplateType $entityType,
        string $uuid,
    ): JsonResponse|JsonResource {
        $listDTO = CommentListDTO::fromRequest($request->validated());

        $result = $this->commentService->getCommentsForEntity(
            entityType: $entityType,
            entityUuid: EntityId::from($uuid),
            dto: $listDTO,
            viewer: $this->currentUserProvider->currentAuthenticatedUser(),
        );

        if ($result->isFailure()) {
            return TypedResults::fromError($result->getError());
        }

        /** @var Comments $comments */
        $comments = $result->getData();

        return CommentListResource::fromPaginated($comments, $listDTO->include_replies);
    }

    private function requiredAuthenticatedUser(): AuthenticatedUser
    {
        return $this->currentUserProvider->currentAuthenticatedUser()
            ?? throw new AuthenticationException;
    }
}
