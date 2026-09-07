<?php

namespace App\Application\Engagement\Services;

use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Domain\Shared\Enums\ObjectTemplateType;

interface EngagementServiceInterface
{
    public function toggleLike(int $userId, int $entityId, ObjectTemplateType $type);

    /**
     * Batch-load Article engagement stats.
     *
     * Takes ids rather than the Articles domain collection: that collection wraps a
     * Laravel paginator, and the list read path must not hand framework objects across
     * application boundaries.
     *
     * @param array<int, int> $articleIds
     *
     * @return array<int, ArticleStats> keyed by Article id
     */
    public function getArticleStatsByIds(array $articleIds): array;

    public function isEntityLikedByViewer(int $entityId, ObjectTemplateType $objectType, bool $isLoggedUser): bool;

    public function getSingleArticleEngagementSummary(int $entityId, ObjectTemplateType $objectType, ArticleIncludeOptionsDTO $includeOptions, bool $isLoggedUser): EngagementSummary;
}
