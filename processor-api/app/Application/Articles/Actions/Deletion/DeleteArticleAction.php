<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Deletion;

use App\Application\Articles\Interfaces\Repositories\ArticleRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Removes an article and everything hanging off it. The author's delete and a moderator's
 * removal both end here, so a new dependent table is added in one place. Runs inside the
 * caller's transaction.
 */
class DeleteArticleAction
{
    public function __construct(
        private readonly ArticleRepositoryInterface $articleRepository,
        private readonly ViewRepositoryInterface $viewRepository,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly LikeRepositoryInterface $likeRepository,
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly HashtagRepositoryInterface $hashtagRepository,
        private readonly CleanupArticleCustomListsAction $cleanupCustomLists,
    ) {
    }

    public function execute(int $articleId): void
    {
        $templateId = ObjectTemplateType::ARTICLE->getLegacyId();

        $this->articleRepository->deleteById($articleId);
        $this->viewRepository->deleteByEntity($articleId, $templateId);
        $this->downloadRepository->deleteByEntity($articleId, $templateId);
        $this->likeRepository->deleteByEntity($articleId, $templateId);
        $this->commentRepository->deleteByEntity($articleId, $templateId);
        $this->hashtagRepository->deleteByEntity($articleId, $templateId);
        $this->cleanupCustomLists->execute($articleId);
    }
}
