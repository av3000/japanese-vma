<?php

declare(strict_types=1);

namespace App\Application\Community\Posts\Actions;

use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Community\Posts\Interfaces\Repositories\PostRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Removes a post and everything keyed to it by (template_id, real_object_id). The author's delete
 * and a moderator's removal both end here, so a new dependent table is added in one place. Runs
 * inside the caller's transaction.
 *
 * The dependents match legacy PostController::removeImpressions() plus removeHashtags(). Comment
 * likes are not listed separately because CommentRepository::deleteByEntity already cascades them.
 * Downloads have no legacy Post path, but the join table is entity-agnostic and a stray row would
 * survive the post; deleting them costs one statement.
 */
final class DeletePostAction
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly ViewRepositoryInterface $viewRepository,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly LikeRepositoryInterface $likeRepository,
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly HashtagRepositoryInterface $hashtagRepository,
    ) {
    }

    public function execute(int $postId): void
    {
        $templateId = ObjectTemplateType::POST->getLegacyId();

        $this->viewRepository->deleteByEntity($postId, $templateId);
        $this->downloadRepository->deleteByEntity($postId, $templateId);
        $this->likeRepository->deleteByEntity($postId, $templateId);
        $this->commentRepository->deleteByEntity($postId, $templateId);
        $this->hashtagRepository->deleteByEntity($postId, $templateId);
        $this->postRepository->delete($postId);
    }
}
