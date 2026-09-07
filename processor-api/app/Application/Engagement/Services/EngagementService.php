<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Engagement\DTOs\EngagementSummary;
use App\Domain\Engagement\DTOs\LikeFilterDTO;
use App\Domain\Engagement\DTOs\ViewFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\Like as PersistenceLike;
use App\Infrastructure\Persistence\Repositories\LikeMapper;

class EngagementService implements EngagementServiceInterface
{
    public function __construct(
        private LoadEntityStatsAction $loadStats,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        // TOOD: should be removed after toggleLike method will go through likeRepository
        private LikeMapper $likeMapper
    ) {
    }

    public function getSingleArticleEngagementSummary(
        int $entityId,
        ObjectTemplateType $objectType,
        ArticleIncludeOptionsDTO $includeOptions,
        bool $isLoggedUser
    ): EngagementSummary {
        $likesCount = $this->likeRepository->countByFilter(new LikeFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));

        $isLiked = $this->isEntityLikedByViewer($entityId, $objectType, $isLoggedUser);

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

    public function isEntityLikedByViewer(int $entityId, ObjectTemplateType $objectType, bool $isLoggedUser): bool
    {
        if (! $isLoggedUser) {
            return false;
        }

        return $this->likeRepository->userLikedByFilter(new LikeFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));
    }

    public function getArticleStatsByIds(array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        $statsData = $this->loadStats->batchLoadStatsById(
            ObjectTemplateType::ARTICLE->getLegacyId(),
            $articleIds
        );

        $statsMap = [];
        foreach ($articleIds as $articleId) {
            $stats = $statsData[$articleId] ?? [
                'likes' => 0,
                'downloads' => 0,
                'views' => 0,
                'comments' => 0,
            ];

            $statsMap[$articleId] = new ArticleStats(
                $stats['likes'],
                $stats['downloads'],
                $stats['views'],
                $stats['comments']
            );
        }

        return $statsMap;
    }

    // TODO: use LikeRepository to query DB instead of leaking persistence model to service and controller layers.
    // TODO: Add return type DomainLike | false
    public function toggleLike(int $userId, int $entityId, ObjectTemplateType $type)
    {
        $like = PersistenceLike::where([
            'user_id' => $userId,
            'real_object_id' => $entityId,
            'template_id' => $type->getLegacyId(),
        ])->first();

        if (! $like) {
            $like = new PersistenceLike();
            $like->user_id = $userId;
            $like->template_id = $type->getLegacyId();
            $like->real_object_id = $entityId;
            $like->value = 1;
            $like->save();
            $like->load('user:id,uuid,name');
            $mappedDomainLike = $this->likeMapper->mapToDomain($like);

            return $mappedDomainLike;
        }

        $like->delete();

        return null;
    }
}
