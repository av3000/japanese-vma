<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Actions;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;

/**
 * Removes a user-authored sentence and everything keyed to it: list entries, views, downloads,
 * likes and comments. The author's delete and a moderator's removal both end here, so a new
 * dependent table is added in one place. Runs inside the caller's transaction.
 */
final class DeleteSentenceAction
{
    public function __construct(
        private readonly SentenceRepositoryInterface $sentenceRepository,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly ViewRepositoryInterface $viewRepository,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly LikeRepositoryInterface $likeRepository,
        private readonly CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function execute(int $sentenceId): void
    {
        $templateId = ObjectTemplateType::SENTENCE->getLegacyId();

        $this->catalogueItemRepository->deleteByItem($sentenceId, [
            SavedListType::SENTENCES,
            SavedListType::KNOWNSENTENCES,
        ]);
        $this->viewRepository->deleteByEntity($sentenceId, $templateId);
        $this->downloadRepository->deleteByEntity($sentenceId, $templateId);
        $this->likeRepository->deleteByEntity($sentenceId, $templateId);
        $this->commentRepository->deleteByEntity($sentenceId, $templateId);
        $this->sentenceRepository->delete($sentenceId);
    }
}
