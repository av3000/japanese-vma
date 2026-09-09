<?php

namespace App\Application\Comments\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentCriteriaDTO;
use App\Domain\Comments\DTOs\CommentListDTO;
use App\Domain\Comments\DTOs\CommentUpdateDTO;
use App\Domain\Comments\Errors\CommentErrors;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Models\Comments;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Pagination;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentService implements CommentServiceInterface
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly CommentEntityResolver $commentEntityResolver,
        private readonly CommentPolicy $commentPolicy,
    ) {
    }

    public function getCommentsForEntity(
        ObjectTemplateType $entityType,
        EntityId $entityUuid,
        CommentListDTO $dto,
        ?AuthenticatedUser $viewer,
    ): Result {
        $entityResult = $this->commentEntityResolver->resolveByUuid($entityType, $entityUuid);

        if ($entityResult->isFailure()) {
            return $entityResult;
        }

        /** @var int $entityId */
        $entityId = $entityResult->getData();

        $criteriaDTO = new CommentCriteriaDTO(
            entityId: $entityId,
            entityType: $entityType,
            pagination: Pagination::fromInputOrDefault($dto->page, $dto->per_page),
            sortBy: $dto->sort_by,
            sortDir: $dto->sort_dir,
        );

        $viewerUserId = $viewer?->id->value();

        $comments = $this->commentRepository->findByCriteriaForEntity(
            criteria: $criteriaDTO,
            viewerUserId: $viewerUserId,
        );

        return Result::success($this->attachReplies($comments, $dto, $viewerUserId));
    }

    public function getRepliesForComment(
        EntityId $commentUuid,
        Pagination $pagination,
        ?AuthenticatedUser $viewer,
    ): Result {
        $comment = $this->commentRepository->findByUuid($commentUuid);

        if ($comment === null) {
            return Result::failure(CommentErrors::notFound($commentUuid->value()));
        }

        return Result::success(
            $this->commentRepository->findRepliesByRoot(
                rootId: $comment->getIdValue(),
                pagination: $pagination,
                viewerUserId: $viewer?->id->value(),
            )
        );
    }

    /**
     * Subtree sizes are attached whether or not previews were requested: a
     * reader needs to know a comment has forty replies before deciding to load
     * them, and the count costs the same query either way.
     */
    private function attachReplies(Comments $comments, CommentListDTO $dto, ?int $viewerUserId): Comments
    {
        $roots = $comments->getItems();

        if ($roots === []) {
            return $comments;
        }

        $replyData = $this->commentRepository->findRepliesForRoots(
            rootIds: array_map(static fn (Comment $root) => $root->getIdValue(), $roots),
            limitPerRoot: $dto->include_replies ? $dto->replies_limit : 0,
            viewerUserId: $viewerUserId,
        );

        $paginator = $comments->getPaginator();

        $paginator->setCollection(
            $paginator->getCollection()->map(
                static fn (Comment $root) => $root->withReplies(
                    $replyData[$root->getIdValue()]['replies'] ?? [],
                    $replyData[$root->getIdValue()]['count'] ?? 0,
                )
            )
        );

        return Comments::fromEloquentPaginator($paginator);
    }

    public function createComment(CommentCreateDTO $dto, AuthenticatedUser $author): Result
    {
        // The create-specific resolver also enforces the parent's write gate: a
        // locked Post stops accepting new comments while its existing thread
        // stays readable, editable and deletable through the paths below.
        $entityResult = $this->commentEntityResolver->resolveIdentityForCreate(
            $dto->entity_type,
            $dto->entity_id,
            $dto->entity_uuid,
        );

        if ($entityResult->isFailure()) {
            return $entityResult;
        }

        if ($dto->parent_comment_id !== null) {
            $parentResult = $this->validateParentComment($dto);

            if ($parentResult->isFailure()) {
                return $parentResult;
            }
        }

        try {
            return Result::success(
                $this->commentRepository->createForEntity(dto: $dto, authorId: $author->id)
            );
        } catch (\Exception $e) {
            Log::error('Comment creation failed', [
                'entity_type' => $dto->entity_type->getTitle(),
                'entity_uuid' => $dto->entity_uuid->value(),
                'user_id' => $author->id->value(),
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CommentErrors::creationFailed());
        }
    }

    public function updateComment(
        EntityId $commentUuid,
        CommentUpdateDTO $dto,
        AuthenticatedUser $actor,
    ): Result {
        $comment = $this->commentRepository->findByUuid($commentUuid);

        if ($comment === null) {
            return Result::failure(CommentErrors::notFound($commentUuid->value()));
        }

        if (! $this->commentPolicy->canUpdate($actor, $comment)) {
            return Result::failure(CommentErrors::accessDenied($commentUuid->value()));
        }

        try {
            return Result::success(
                $this->commentRepository->updateContent($comment->getIdValue(), $dto->content)
            );
        } catch (\Exception $e) {
            Log::error('Comment update failed', [
                'comment_uuid' => $commentUuid->value(),
                'user_id' => $actor->id->value(),
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CommentErrors::updateFailed());
        }
    }

    /**
     * Deleting a comment removes the whole subtree beneath it. Replies have no
     * meaning without the comment they answer, and leaving them behind would
     * strand rows pointing at a missing parent.
     */
    public function deleteComment(EntityId $commentUuid, AuthenticatedUser $actor): Result
    {
        $comment = $this->commentRepository->findByUuid($commentUuid);

        if ($comment === null) {
            return Result::failure(CommentErrors::notFound($commentUuid->value()));
        }

        if (! $this->commentPolicy->canDelete($actor, $comment)) {
            return Result::failure(CommentErrors::accessDenied($commentUuid->value()));
        }

        try {
            DB::transaction(function () use ($comment) {
                $commentId = $comment->getIdValue();

                // Deepest replies first, so no row is orphaned mid-transaction.
                $descendantIds = $this->commentRepository->collectDescendantIds($commentId);

                $this->commentRepository->deleteWithLikesByIds(
                    [...$descendantIds, $commentId]
                );
            });

            return Result::success();
        } catch (\Exception $e) {
            Log::error('Comment deletion failed', [
                'comment_uuid' => $commentUuid->value(),
                'user_id' => $actor->id->value(),
                'error' => $e->getMessage(),
            ]);

            return Result::failure(CommentErrors::deletionFailed());
        }
    }

    /**
     * A reply may answer any comment, including another reply, but it must stay
     * on the entity thread it was posted to.
     */
    private function validateParentComment(CommentCreateDTO $dto): Result
    {
        $parent = $this->commentRepository->findById($dto->parent_comment_id);

        if ($parent === null) {
            return Result::failure(CommentErrors::parentCommentNotFound($dto->parent_comment_id));
        }

        if (! $this->isSameEntity($parent, $dto)) {
            return Result::failure(
                CommentErrors::parentCommentEntityMismatch($dto->parent_comment_id)
            );
        }

        return Result::success();
    }

    private function isSameEntity(Comment $parent, CommentCreateDTO $dto): bool
    {
        return $parent->getEntityType() === $dto->entity_type
            && $parent->getEntityId() === $dto->entity_id;
    }
}
