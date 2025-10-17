<?php
namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityHashtagsAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Engagement\Interfaces\Repositories\LikeRepositoryInterface;
use App\Domain\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Domain\Engagement\Interfaces\Repositories\CommentRepositoryInterface;
use App\Domain\Engagement\Interfaces\Services\EngagementServiceInterface;
use App\Domain\Engagement\Models\EngagementData;
use App\Domain\Engagement\DTOs\{ViewFilterDTO, EngagementFilterDTO};
use App\Domain\Articles\DTOs\ArticleIncludeOptionsDTO;
use App\Domain\Articles\Models\{Articles, Article};

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EngagementService implements EngagementServiceInterface
{
   public function __construct(
        private LoadEntityStatsAction $loadStats,
        private ViewRepositoryInterface $viewRepository
    ) {}

    // TODO: probably should have generic instance that takes LengthAwarePaginator and entity type, then calls specific methods based on type
    // public function enhanceArticlesWithStats(Articles $articles): Articles
    // {
    //     if ($articles->isEmpty()) {
    //         return $articles;
    //     }

    //     $articleUuids = array_map(function($article) {
    //         return $article->getUid()->value();
    //     }, $articles->getItems());

    //     $statsData = $this->loadStats->batchLoadStatsByUuid(
    //         ObjectTemplateType::ARTICLE->value,
    //         $articleUuids
    //     );

    //     return $articles->transform(function($article) use ($statsData) {
    //         $articleUuid = $article->getUid()->value();
    //         $stats = $statsData[$articleUuid] ?? [
    //             'likes' => 0,
    //             'downloads' => 0,
    //             'views' => 0,
    //             'comments' => 0
    //         ];

    //         return $article->withStats(
    //             $stats['likes'],
    //             $stats['downloads'],
    //             $stats['views'],
    //             $stats['comments']
    //         );
    //     });
    // }
    public function getEngagementData(
        int $entityId,
        ObjectTemplateType $objectType,
        ArticleIncludeOptionsDTO $dto
    ) : EngagementData {
        $views = null;
        $likes = null;
        $downloads = null;
        $comments = null;

        if ($dto->include_views) {
            $views = $this->viewRepository->findAllByFilter(new ViewFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($dto->include_likes) {
            $likes = $this->likeRepository->findAllByFilter(new EngagementFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($dto->include_downloads) {
            $downloads = $this->downloadRepository->findAllByFilter(new EngagementFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        if ($dto->include_comments) {
            $comments = $this->commentRepository->findAllByFilter(new EngagementFilterDTO(
                entityId: $entityId,
                objectType: $objectType
            ));
        }

        return new EngagementData($views, $likes, $downloads, $comments);
    }

    public function enhanceArticleWithStats(Article $article): Article
    {
        $statsData = $this->loadStats->batchLoadStatsById(
            ObjectTemplateType::ARTICLE->value,
            [$article->getIdValue()]
        );

        return $this->applyStatsToArticle($article, $statsData);
    }

    public function enhanceArticlesWithStats(Articles $articles): Articles
    {
        if ($articles->isEmpty()) {
            return $articles;
        }

        $articleUuids = array_map(function($article) {
            return $article->getIdValue();
        }, $articles->getItems());

        $statsData = $this->loadStats->batchLoadStatsById(
            ObjectTemplateType::ARTICLE->value,
            $articleUuids
        );

        return $articles->transform(function($article) use ($statsData) {
            return $this->applyStatsToArticle($article, $statsData);
        });
    }

    public function enhanceWithViews(int $entityId, ObjectTemplateType $objectType): Collection
    {
        return $this->viewRepository->findAllByFilter(new ViewFilterDTO(
            entityId: $entityId,
            objectType: $objectType
        ));
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


}
