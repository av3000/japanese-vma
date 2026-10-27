<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Application\Activity\ActivityEntries;
use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Community\Posts\Actions\DeletePostAction;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Community\Posts\Policies\PostModerationPolicy;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Domain\Community\Posts\Errors\PostErrors;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PostModerationService implements PostModerationServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly PostModerationPolicy $moderationPolicy,
        private readonly DeletePostAction $deletePost,
        private readonly ActivityRecorderInterface $activityRecorder,
    ) {
    }

    public function setLocked(EntityId $uuid, bool $locked, AuthenticatedUser $actor): Result
    {
        if (! $this->moderationPolicy->canModerate($actor)) {
            return Result::failure(PostErrors::accessDenied($uuid->value()));
        }

        $post = $this->postRepository->findByUuid($uuid);

        if ($post === null) {
            return Result::failure(PostErrors::notFound($uuid->value()));
        }

        if ($post->isLocked() === $locked) {
            return Result::success($post);
        }

        try {
            $updated = DB::transaction(function () use ($post, $locked, $uuid, $actor): Post {
                $this->postRepository->setLocked($post->getIdValue(), $locked);

                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    $locked ? ActivityAction::PostLocked : ActivityAction::PostUnlocked,
                    ActivitySubjectType::Post,
                    $uuid->value(),
                    $post->getTitle(),
                    before: ['locked' => ! $locked],
                    after: ['locked' => $locked],
                ));

                return $this->postRepository->findByUuid($uuid)
                    ?? throw new RuntimeException('Locked post could not be reloaded.');
            });

            return Result::success($updated);
        } catch (Throwable $exception) {
            Log::error('Post moderation lock update failed', [
                'user_id' => $actor->id->value(),
                'post_uuid' => $uuid->value(),
                'locked' => $locked,
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::lockUpdateFailed());
        }
    }

    public function remove(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        if (! $this->moderationPolicy->canModerate($actor)) {
            return Result::failure(PostErrors::accessDenied($uuid->value()));
        }

        $post = $this->postRepository->findByUuid($uuid);

        if ($post === null) {
            return Result::failure(PostErrors::notFound($uuid->value()));
        }

        try {
            DB::transaction(function () use ($post, $actor): void {
                $this->activityRecorder->record(ActivityEntries::by(
                    $actor,
                    ActivityAction::PostDeleted,
                    ActivitySubjectType::Post,
                    $post->getUuid()->value(),
                    $post->getTitle(),
                    before: [
                        'title' => $post->getTitle(),
                        'content' => $post->getContent(),
                        'topic' => $post->getTopic()->label(),
                        'locked' => $post->isLocked(),
                        'author' => $post->getAuthorName(),
                    ],
                ));

                $this->deletePost->execute($post->getIdValue());
            });
        } catch (Throwable $exception) {
            Log::error('Post moderation removal failed', [
                'user_id' => $actor->id->value(),
                'post_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::deletionFailed());
        }

        return Result::success();
    }
}
