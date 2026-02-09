<?php

namespace App\Application\Engagement\Services;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles};
use App\Domain\Engagement\DTOs\EngagementSummary;

interface EngagementServiceInterface
{
    public function toggleLike(int $userId, int $entityId, ObjectTemplateType $type);
    public function enhanceArticlesWithStatsCounts(Articles $articles): array;
    public function getSingleArticleEngagementSummary(int $entityId, ObjectTemplateType $objectType, ArticleIncludeOptionsDTO $includeOptions, bool $isLoggedUser): EngagementSummary;
}
