<?php
namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityHashtagsAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Application\Engagement\Interfaces\Repositories\{ViewRepositoryInterface, LikeRepositoryInterface, CommentRepositoryInterface, DownloadRepositoryInterface};
use App\Domain\Engagement\Models\EngagementData;
use App\Domain\Engagement\DTOs\{ViewFilterDTO, EngagementFilterDTO, LikeFilterDTO};
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles, Article, ArticleWithEnhancements, ArticleStats};


use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
    ) : EngagementData {
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
            $downloads = $this->downloadRepository->findAllByFilter(new EngagementFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($includeOptions->include_comments) {
            $comments = $this->commentRepository->findAllByFilter(new EngagementFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        return new EngagementData($views, $likes, $downloads, $comments);
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
                'likes' => 0, 'downloads' => 0, 'views' => 0, 'comments' => 0
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

    public function enhanceWithComments($article): void
    {
        dd('Not implemented yet', $article);
    }

    public function getArticleListBatchEngagementData(
        array $entityIds,
        ObjectTemplateType $objectType,
    ): array {
        $batchData = [];

        foreach ($entityIds as $entityId) {
            $batchData[$entityId] = [
                'views' => [],
                'likes' => [],
                'downloads' => [],
                'comments' => []
            ];
        }

        $viewsData = $this->viewRepository->findAllByEntityIds($entityIds, $objectType);
        $likesData = $this->likeRepository->findAllByEntityIds($entityIds, $objectType);
        $downloadsData = $this->downloadRepository->findAllByEntityIds($entityIds, $objectType);
        $commentsData = $this->commentRepository->findAllByEntityIds($entityIds, $objectType);

        foreach ($viewsData as $entityId => $views) {
            $batchData[$entityId]['views'] = $views;
        }
        foreach ($likesData as $entityId => $likes) {
            $batchData[$entityId]['likes'] = $likes;
        }
        foreach ($downloadsData as $entityId => $downloads) {
            $batchData[$entityId]['downloads'] = $downloads;
        }
        foreach ($commentsData as $entityId => $comments) {
            $batchData[$entityId]['comments'] = $comments;
        }

        $result = [];
        foreach ($batchData as $entityId => $data) {
            $result[$entityId] = new EngagementData(
                $data['views'],
                $data['likes'],
                $data['downloads'],
                $data['comments']
            );
        }

        return $result;
    }
}
