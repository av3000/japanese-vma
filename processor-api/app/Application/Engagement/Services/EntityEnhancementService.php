<?php
namespace App\Application\Engagement\Services;

use App\Application\Engagement\Actions\LoadEntityHashtagsAction;
use App\Application\Engagement\Actions\LoadEntityStatsAction;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Articles\Models\Articles;

use Illuminate\Pagination\LengthAwarePaginator;

class EntityEnhancementService implements EntityEnhancementServiceInterface
{
   public function __construct(
        private LoadEntityStatsAction $loadStats
    ) {}

    public function enhanceArticlesWithStats(Articles $articles): Articles
    {
        if ($articles->isEmpty()) {
            return $articles;
        }

        $articleUuids = array_map(function($article) {
            return $article->getUid()->value();
        }, $articles->getItems());

        $statsData = $this->loadStats->batchLoadStatsByUuid(
            ObjectTemplateType::ARTICLE->value,
            $articleUuids
        );

        return $articles->transform(function($article) use ($statsData) {
            $articleUuid = $article->getUid()->value();
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
        });
    }


}
