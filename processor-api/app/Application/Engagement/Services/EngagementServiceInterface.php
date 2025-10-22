<?php

namespace App\Application\Engagement\Services;

use App\Domain\Engagement\Models\EngagementData;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles, Article};

interface EngagementServiceInterface
{
    public function enhanceArticlesWithStatsCounts(Articles $articles): array;
    public function enhanceWithComments($article): void;
    public function getSingleArticleEngagementData(int $entityId, ObjectTemplateType $objectType, ArticleIncludeOptionsDTO $includeOptions): EngagementData;
    public function getArticleListBatchEngagementData(array $entityIds, ObjectTemplateType $objectType): array;
}
