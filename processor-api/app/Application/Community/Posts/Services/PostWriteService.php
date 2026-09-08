<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Services;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Community\Posts\Actions\CleanupPostDependenciesAction;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Community\Posts\Policies\PostPolicy;
use App\Application\Engagement\Actions\IncrementViewAction;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Community\Posts\DTOs\PostCreateDTO;
use App\Domain\Community\Posts\DTOs\PostUpdateDTO;
use App\Domain\Community\Posts\Errors\PostErrors;
use App\Domain\Community\Posts\Exceptions\PostWriteFailedException;
use App\Domain\Community\Posts\Models\Post;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Domain\Shared\ValueObjects\Viewer;
use App\Shared\Results\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PostWriteService implements PostWriteServiceInterface
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly PostReadServiceInterface $postReadService,
        private readonly PostPolicy $postPolicy,
        private readonly HashtagServiceInterface $hashtagService,
        private readonly CleanupPostDependenciesAction $cleanupPostDependencies,
        private readonly IncrementViewAction $incrementView,
    ) {
    }

    public function create(PostCreateDTO $dto, AuthenticatedUser $actor, Viewer $viewer): Result
    {
        $uuid = EntityId::from((string) Str::uuid());

        try {
            $post = DB::transaction(function () use ($dto, $actor, $uuid): Post {
                $created = $this->postRepository->create($dto, $actor->id, $uuid);

                $this->syncTagsOrFail($created->getIdValue(), $dto->tags, $actor);

                return $created;
            });
        } catch (PostWriteFailedException $exception) {
            return Result::failure($exception->error);
        } catch (Throwable $exception) {
            Log::error('Post creation failed', [
                'user_id' => $actor->id->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::creationFailed());
        }

        // Legacy PostController::store() recorded a view for the author right
        // after save, so a new Post reads as one view rather than zero. Kept
        // outside the transaction: it is a counter, not part of the Post write,
        // and a failure here must not discard a Post the author already wrote.
        $this->recordInitialView($post->getIdValue(), $viewer);

        return Result::success($this->postReadService->describe($post));
    }

    public function update(EntityId $uuid, PostUpdateDTO $dto, AuthenticatedUser $actor): Result
    {
        $post = $this->postRepository->findByUuid($uuid);

        if ($post === null) {
            return Result::failure(PostErrors::notFound($uuid->value()));
        }

        if (! $this->postPolicy->canUpdate($actor, $post)) {
            return Result::failure(PostErrors::accessDenied($uuid->value()));
        }

        try {
            $updated = DB::transaction(function () use ($post, $dto, $uuid, $actor): Post {
                $this->postRepository->update($post->getIdValue(), $dto);

                // Null means the caller did not mention tags; [] means clear them.
                if ($dto->tags !== null) {
                    $this->syncTagsOrFail($post->getIdValue(), $dto->tags, $actor);
                }

                return $this->postRepository->findByUuid($uuid)
                    ?? throw new RuntimeException('Updated post could not be reloaded.');
            });
        } catch (PostWriteFailedException $exception) {
            return Result::failure($exception->error);
        } catch (Throwable $exception) {
            Log::error('Post update failed', [
                'user_id' => $actor->id->value(),
                'post_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::updateFailed());
        }

        return Result::success($this->postReadService->describe($updated));
    }

    public function delete(EntityId $uuid, AuthenticatedUser $actor): Result
    {
        $post = $this->postRepository->findByUuid($uuid);

        if ($post === null) {
            return Result::failure(PostErrors::notFound($uuid->value()));
        }

        if (! $this->postPolicy->canDelete($actor, $post)) {
            return Result::failure(PostErrors::accessDenied($uuid->value()));
        }

        try {
            DB::transaction(function () use ($post): void {
                $this->cleanupPostDependencies->execute($post->getIdValue());
                $this->postRepository->delete($post->getIdValue());
            });

            return Result::success();
        } catch (Throwable $exception) {
            Log::error('Post deletion failed', [
                'user_id' => $actor->id->value(),
                'post_uuid' => $uuid->value(),
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::deletionFailed());
        }
    }

    public function setLocked(EntityId $uuid, bool $locked, AuthenticatedUser $actor): Result
    {
        if (! $this->postPolicy->canLock($actor)) {
            return Result::failure(PostErrors::accessDenied($uuid->value()));
        }

        $post = $this->postRepository->findByUuid($uuid);

        if ($post === null) {
            return Result::failure(PostErrors::notFound($uuid->value()));
        }

        // Setting the state the Post already has is a no-op rather than a
        // pointless write; the response is identical either way, which is what
        // makes this endpoint safe to retry.
        if ($post->isLocked() === $locked) {
            return Result::success($post);
        }

        try {
            $updated = DB::transaction(function () use ($post, $locked, $uuid): Post {
                $this->postRepository->setLocked($post->getIdValue(), $locked);

                return $this->postRepository->findByUuid($uuid)
                    ?? throw new RuntimeException('Locked post could not be reloaded.');
            });

            return Result::success($updated);
        } catch (Throwable $exception) {
            Log::error('Post lock update failed', [
                'user_id' => $actor->id->value(),
                'post_uuid' => $uuid->value(),
                'locked' => $locked,
                'error' => $exception->getMessage(),
            ]);

            return Result::failure(PostErrors::lockUpdateFailed());
        }
    }

    /**
     * @param array<int, string> $tags
     */
    private function syncTagsOrFail(int $postId, array $tags, AuthenticatedUser $actor): void
    {
        $result = $this->hashtagService->syncTagsForEntity(
            $postId,
            ObjectTemplateType::POST,
            $tags,
            $actor->id->value(),
        );

        if (! $result->isFailure()) {
            return;
        }

        $error = $result->getError();

        // A rejected tag is the caller's fault and answers 422. HashtagErrors
        // labels it 404, which would be indistinguishable from an unknown Post.
        if ($error->code === 'Hashtags.InvalidTag') {
            throw new PostWriteFailedException(
                PostErrors::invalidTags($error->detail ?? $error->description),
            );
        }

        // Anything else is an infrastructure failure. Let the caller's catch
        // turn it into the slice's own 500 rather than blaming the request.
        throw new RuntimeException("Hashtag sync failed: {$error->code}");
    }

    private function recordInitialView(int $postId, Viewer $viewer): void
    {
        try {
            $this->incrementView->execute($postId, ObjectTemplateType::POST, $viewer);
        } catch (Throwable $exception) {
            Log::error('Failed to record initial post view', [
                'post_id' => $postId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
