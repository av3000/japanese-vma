<?php

namespace App\Application\Engagement\Services;

use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles};
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Domain\Shared\Enums\ObjectTemplateType;

interface EngagementServiceInterface
{
    public function enhanceArticlesWithStatsCounts(Articles $articles): array;

    public function isEntityLikedByViewer(int $entityId, ObjectTemplateType $objectType, ?int $viewerUserId): bool;

    public function getSingleArticleEngagementSummary(int $entityId, ObjectTemplateType $objectType, ArticleIncludeOptionsDTO $includeOptions, ?int $viewerUserId): EngagementSummary;
}
