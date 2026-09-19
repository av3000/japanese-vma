<?php

namespace App\Application\Comments\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Interfaces\Readers\CommentThreadReaderInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Comments\Policies\CommentPolicy;
use App\Domain\Comments\DTOs\CommentCreateDTO;
use App\Domain\Comments\DTOs\CommentListIncludes;
use App\Domain\Comments\DTOs\CommentListItemDTO;
use App\Domain\Comments\DTOs\CommentListResultDTO;
use App\Domain\Comments\DTOs\CommentRepliesPreviewDTO;
use App\Domain\Comments\DTOs\CommentUpdateDTO;
use App\Domain\Comments\Errors\CommentErrors;
use App\Domain\Comments\Models\Comment;
use App\Domain\Comments\Queries\CommentQueryCriteria;
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
        private readonly CommentThreadReaderInterface $commentThreadReader,
        private readonly CommentEntityResolver $commentEntityResolver,
        private readonly CommentPolicy $commentPolicy,
    ) {
    }

    public function getCommentsForEntity(
        ObjectTemplateType $entityType,
        EntityId $entityUuid,
        CommentQueryCriteria $criteria,
        CommentListIncludes $includes,
        ?AuthenticatedUser $viewer,
    ): Result {
        $entityResult = $this->commentEntityResolver->resolveByUuid($entityType, $entityUuid);

        if ($entityResult->isFailure()) {
            return $entityResult;
        }

        /** @var int $entityId */
        $entityId = $entityResult->getData();

        $viewerUserId = $viewer?->id->value();

        $page = $this->commentThreadReader->rootPage(
            entityId: $entityId,
            entityType: $entityType,
            criteria: $criteria,
            viewerUserId: $viewerUserId,
        );

        return Result::success(new CommentListResultDTO(
            items: $this->attachReplies($page->comments, $includes, $viewerUserId),
            pagination: $page->pagination,
        ));
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
            $this->commentThreadReader->replyPage(
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
     *
     * @param array<int, Comment> $roots
     *
     * @return array<int, CommentListItemDTO>
     */
    private function attachReplies(array $roots, CommentListIncludes $includes, ?int $viewerUserId): array
    {
        if ($roots === []) {
            return [];
        }

        $previews = $this->commentThreadReader->replyPreviews(
            rootIds: array_map(static fn (Comment $root): int => $root->getIdValue(), $roots),
            limitPerRoot: $includes->previewLimit(),
            viewerUserId: $viewerUserId,
        );

        return array_map(
            static fn (Comment $root): CommentListItemDTO => CommentListItemDTO::fromPreview(
                $root,
                $previews[$root->getIdValue()] ?? CommentRepliesPreviewDTO::empty(),
            ),
            $roots,
        );
    }

    /**
     * A write response reports the same subtree size a read would, so a client
     * can drop it straight into its cached thread. Previews are left empty: an
     * edit does not re-read the subtree, and the caller keeps the replies it
     * already has.
     */
    private function withThreadSize(Comment $comment): CommentListItemDTO
    {
        $previews = $this->commentThreadReader->replyPreviews(
            rootIds: [$comment->getIdValue()],
            limitPerRoot: 0,
            viewerUserId: null,
        );

        return CommentListItemDTO::fromPreview(
            $comment,
            $previews[$comment->getIdValue()] ?? CommentRepliesPreviewDTO::empty(),
        );
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
            $created = $this->commentRepository->createForEntity(dto: $dto, authorId: $author->id);

            // A comment that has just been created has nothing beneath it, so the
            // subtree size is known without asking the database for it.
            return Result::success(new CommentListItemDTO($created, 0, []));
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
                $this->withThreadSize(
                    $this->commentRepository->updateContent($comment->getIdValue(), $dto->content)
                )
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
