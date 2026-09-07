<?php

declare(strict_types=1);

namespace App\Application\JapaneseMaterial\Sentences\Actions;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\Enums\SavedListType;

final class CleanupSentenceDependenciesAction
{
    public function __construct(
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
    }
}
