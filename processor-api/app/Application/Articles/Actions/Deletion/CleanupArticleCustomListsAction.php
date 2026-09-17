<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Deletion;

use App\Application\Catalogues\Interfaces\Repositories\CatalogueItemRepositoryInterface;
use App\Domain\Shared\Enums\SavedListType;

final class CleanupArticleCustomListsAction
{
    public function __construct(
        private readonly CatalogueItemRepositoryInterface $catalogueItemRepository,
    ) {
    }

    /**
     * `customlist_object.listtype_id` is keyed by SavedListType, not by
     * ObjectTemplateType: the two taxonomies disagree (an Article is 9 in the
     * first and 1 in the second), so the enum this reaches for is not
     * interchangeable. Going through the repository keeps that choice in one
     * place, the same way CleanupSentenceDependenciesAction does.
     */
    public function execute(int $id): void
    {
        $this->catalogueItemRepository->deleteByItem($id, [SavedListType::ARTICLES]);
    }
}
