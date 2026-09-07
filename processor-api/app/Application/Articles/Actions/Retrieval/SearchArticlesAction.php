<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Retrieval;

use App\Application\Articles\DTOs\ArticleListItemDTO;
use App\Application\Articles\DTOs\ArticleListPageDTO;
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * The Article discovery use case.
 *
 * It owns exactly two things: deriving the mandatory visibility scope from the
 * actor, and batching enrichment around whichever reader is bound. It contains no
 * Eloquent or vendor query syntax, so a future search-engine reader can be
 * swapped in underneath without touching authorization or enrichment.
 */
final readonly class SearchArticlesAction
{
    public function __construct(
        private ArticleListReaderInterface $articleListReader,
        private ArticleProcessingStateReaderInterface $processingStateReader,
        private ArticlePolicy $articlePolicy,
        private EngagementServiceInterface $engagementService,
        private HashtagServiceInterface $hashtagService,
    ) {
    }

    public function execute(
        ArticleListQuery $query,
        ArticleListProjection $projection,
        ?AuthenticatedUser $actor = null,
    ): ArticleListPageDTO {
        $scope = $this->articlePolicy->scopeFor($actor);

        $result = $this->articleListReader->search($query, $scope, $projection);

        $articleIds = array_map(
            static fn (DomainArticle $article): int => $article->getIdValue(),
            $result->articles,
        );
        $articleUuids = array_map(
            static fn (DomainArticle $article): string => $article->getUid()->value(),
            $result->articles,
        );

        $statsMap = $projection->includeStats
            ? $this->engagementService->getArticleStatsByIds($articleIds)
            : [];

        $hashtagsMap = $projection->includeHashtags
            ? $this->hashtagService->getBatchHashtags($articleIds, ObjectTemplateType::ARTICLE)
            : [];

        $processingStates = $this->processingStateReader->latestKanjiExtractionStates($articleUuids);

        $items = array_map(
            static fn (DomainArticle $article): ArticleListItemDTO => new ArticleListItemDTO(
                article: $article,
                stats: $statsMap[$article->getIdValue()] ?? null,
                hashtags: $hashtagsMap[$article->getIdValue()] ?? [],
                processingState: $processingStates[$article->getUid()->value()] ?? null,
            ),
            $result->articles,
        );

        // Counted from the same scope the items came from. If these were built from a
        // separately derived predicate they could drift, and a drifting count is how a
        // private Article leaks: the row stays hidden but still shows up in "N2 (13)".
        $facets = $projection->includeFacets
            ? $this->articleListReader->facets($query, $scope)
            : [];

        return new ArticleListPageDTO(
            items: $items,
            pagination: $result->pagination,
            projection: $projection,
            facets: $facets,
            query: $query->toCanonicalArray(),
        );
    }
}
