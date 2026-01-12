<?php

namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Application\Engagement\Interfaces\Repositories\{ViewRepositoryInterface, LikeRepositoryInterface, DownloadRepositoryInterface};
use App\Application\Comments\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\Models\EngagementData;
use App\Domain\Engagement\DTOs\{ViewFilterDTO, LikeFilterDTO, DownloadFilterDTO};
use App\Domain\Engagement\DTOs\CommentFilterDTO;
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles, Article, ArticleStats};

class EngagementService implements EngagementServiceInterface
{
    public function __construct(
        private LoadEntityStatsAction $loadStats,
        private ViewRepositoryInterface $viewRepository,
        private LikeRepositoryInterface $likeRepository,
        private DownloadRepositoryInterface $downloadRepository,
        private CommentRepositoryInterface $commentRepository
    ) {}

    public function getSingleArticleEngagementData(
        int $entityId,
        ObjectTemplateType $objectType,
        ArticleIncludeOptionsDTO $includeOptions
    ): EngagementData {

        $views = [];
        $likes = [];
        $downloads = [];
        $comments = [];

        if ($includeOptions->include_views) {
            $views = $this->viewRepository->findAllByFilter(new ViewFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($includeOptions->include_likes) {
            $likes = $this->likeRepository->findAllByFilter(new LikeFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($includeOptions->include_downloads) {
            $downloads = $this->downloadRepository->findAllByFilter(new DownloadFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($includeOptions->include_comments) {
            $comments = $this->commentRepository->findAllByFilter(new CommentFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        return new EngagementData($views, $likes, $downloads, $comments);
    }

    // TODO: create generic paginated list model with generic item object of following properties
    // Is paginated list
    // has isEmpty();
    // has getItems();
    // has getIdValue();
    // has getEntityType();
    // has IncludeOptionsDTO created to pass optional booleans of which stats should be included: likes, views, downloads, comments.
    public function getLikesForList(array $entitiesList): array
    {
        $entityIds = array_map(fn($entity) => $entity->getIdValue(), $entitiesList);

        // TODO: create method to move this fetch likes for list of ids via likeRepository
        $statsData = $this->loadStats->batchLoadLikesByEntityIds($entityIds);
        $likesMap = [];
        foreach ($entityIds as $id) {
            $likesMap[$id] = $statsData[$id]['likes'] ?? 0;
        }

        return $likesMap;
    }

    public function enhanceArticlesWithStatsCounts(Articles $articles): array
    {
        if ($articles->isEmpty()) {
            return $articles;
        }

        $articleIds = array_map(fn($article) => $article->getIdValue(), $articles->getItems());
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
                'comments' => 0
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

    private function applyStatsToArticle(Article $article, array $statsData): Article
    {
        $articleUuid = $article->getIdValue();
        $stats = $statsData[$articleUuid] ?? [
            'likes' => 0,
            'downloads' => 0,
            'views' => 0,
            'comments' => 0
        ];

        return $article->withStats(
            $stats['likes'],
            $stats['downloads'],
            $stats['views'],
            $stats['comments']
        );
    }
}
