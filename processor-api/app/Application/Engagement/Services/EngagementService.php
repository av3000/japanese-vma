<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\Articles;
use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Domain\Engagement\DTOs\LikeFilterDTO;
use App\Domain\Engagement\DTOs\ViewFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;

class EngagementService implements EngagementServiceInterface
{
    public function __construct(
        private LoadEntityStatsAction $loadStats,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
    ) {
    }

    public function getSingleArticleEngagementSummary(
        int $entityId,
        ObjectTemplateType $objectType,
        ArticleIncludeOptionsDTO $includeOptions,
        ?int $viewerUserId
    ): EngagementSummary {
        $likesCount = $this->likeRepository->countByFilter(new LikeFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));

        $isLiked = $this->isEntityLikedByViewer($entityId, $objectType, $viewerUserId);

        $viewsCount = $this->viewRepository->countByFilter(new ViewFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));

        $downloadsCount = $this->downloadRepository->countByFilter(new DownloadFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));

        return new EngagementSummary(
            likesCount: $likesCount,
            viewsCount: $viewsCount,
            downloadsCount: $downloadsCount,
            isLikedByViewer: $isLiked
        );
    }

    public function isEntityLikedByViewer(int $entityId, ObjectTemplateType $objectType, ?int $viewerUserId): bool
    {
        if ($viewerUserId === null) {
            return false;
        }

        return $this->likeRepository->userLikedByFilter(new LikeFilterDTO(
            entityId: $entityId,
            objectType: $objectType,
            userId: $viewerUserId
        ));
    }

    public function enhanceArticlesWithStatsCounts(Articles $articles): array
    {
        if ($articles->isEmpty()) {
            return [];
        }

        $articleIds = array_map(fn ($article) => $article->getIdValue(), $articles->getItems());
        $statsData = $this->loadStats->batchLoadStatsById(
            ObjectTemplateType::ARTICLE->getLegacyId(),
            $articleIds
        );

        $statsMap = [];
        foreach ($articles->getItems() as $article) {
            $stats = $statsData[$article->getIdValue()] ?? [
                'likes' => 0,
                'downloads' => 0,
                'views' => 0,
                'comments' => 0,
            ];

            $statsMap[$article->getIdValue()] = new ArticleStats(
                $stats['likes'],
                $stats['downloads'],
                $stats['views'],
                $stats['comments']
            );
        }

        return $statsMap;
    }
}
