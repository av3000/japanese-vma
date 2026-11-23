<?php

declare(strict_types=1);

namespace App\Application\Catalogues\Actions;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Application\Catalogues\Interfaces\Repositories\CatalogueRepositoryInterface;
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\HashtagRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * Removes a catalogue and everything hanging off it. The owner's delete and a moderator's
 * removal both end here, so a new dependent table is added in one place. Runs inside the
 * caller's transaction.
 */
class DeleteCatalogueAction
{
    public function __construct(
        private readonly CatalogueRepositoryInterface $catalogueRepository,
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
        private readonly ViewRepositoryInterface $viewRepository,
        private readonly DownloadRepositoryInterface $downloadRepository,
        private readonly LikeRepositoryInterface $likeRepository,
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly HashtagRepositoryInterface $hashtagRepository,
    ) {
    }

    public function execute(int $catalogueId): void
    {
        $templateId = ObjectTemplateType::LIST->getLegacyId();

        $this->catalogueItemRepository->deleteByCatalogueId($catalogueId);
        $this->viewRepository->deleteByEntity($catalogueId, $templateId);
        $this->downloadRepository->deleteByEntity($catalogueId, $templateId);
        $this->likeRepository->deleteByEntity($catalogueId, $templateId);
        $this->commentRepository->deleteByEntity($catalogueId, $templateId);
        $this->hashtagRepository->deleteByEntity($catalogueId, $templateId);
        $this->catalogueRepository->deleteById($catalogueId);
    }
}
