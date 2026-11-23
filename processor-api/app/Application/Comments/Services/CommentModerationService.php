<?php

declare(strict_types=1);

namespace App\Application\Comments\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Comments\Actions\DeleteCommentThreadAction;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Comments\Policies\CommentModerationPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Comments\DTOs\CommentSnapshotDTO;
use App\Domain\Comments\Errors\CommentErrors;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommentModerationService implements CommentModerationServiceInterface
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly CommentModerationPolicy $moderationPolicy,
        private readonly DeleteCommentThreadAction $deleteCommentThread,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    public function remove(EntityId $commentUuid, AuthenticatedUser $actor): Result
    {
        if (! $this->moderationPolicy->canModerate($actor)) {
            return Result::failure(CommentErrors::accessDenied($commentUuid->value()));
        }

        $comment = $this->commentRepository->findByUuid($commentUuid);

        if ($comment === null) {
            return Result::failure(CommentErrors::notFound($commentUuid->value()));
        }

        try {
            DB::transaction(function () use ($comment, $actor): void {
                $replies = $this->deleteCommentThread->execute($comment->getIdValue());

                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::CommentDeleted,
                    ActivitySubjectType::Comment,
                    $comment->getUuid()->value(),
                    mb_substr($comment->getContent(), 0, 120),
                    before: [
                        'content' => $comment->getContent(),
                        'author' => $comment->getAuthorName(),
                        'entity_type' => $comment->getEntityType()->label(),
                        'entity_id' => $comment->getEntityId(),
                        'parent_comment_id' => $comment->getParentCommentId(),
                    ],
                    // Comments have no soft delete, so the replies' text survives only here.
                    context: [
                        'replies_deleted' => count($replies),
                        'replies' => array_map(static fn (CommentSnapshotDTO $reply): array => $reply->toArray(), $replies),
                    ],
                ));
            });
        } catch (Throwable $exception) {
            Log::error('Comment moderation removal failed', [
                'comment_uuid' => $commentUuid->value(),
                'user_id' => $actor->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(CommentErrors::deletionFailed());
        }

        return Result::success();
    }
}
