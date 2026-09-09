<?php

declare(strict_types=1);

namespace App\Application\Articles\Actions\Retrieval;

use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\DTOs\ArticleListItemDTO;
use App\Domain\Articles\DTOs\ArticleListResultDTO;
use App\Domain\Articles\Models\Article as DomainArticle;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Shared\Enums\ObjectTemplateType;

/**
 * The Article discovery use case.
 *
 * It owns exactly two things: deriving the mandatory visibility scope from the
 * actor, and batching enrichment around whichever reader is bound. It contains no
 * Eloquent or vendor query syntax, so a future search-engine reader can be
 * swapped in underneath without touching authorization or enrichment.
 */
/*
 * Not final: KanjiDetailService and WordDetailService depend on this directly, and
 * their unit tests double it. Introducing an interface purely to satisfy PHPUnit
 * would add a layer that nothing else needs.
 */
readonly class SearchArticlesAction
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
        ArticleQueryCriteria $criteria,
        ArticleListIncludes $includes,
        ?AuthenticatedUser $actor = null,
    ): ArticleListResultDTO {
        $scope = $this->articlePolicy->scopeFor($actor);

        $result = $this->articleListReader->search($criteria, $scope, $includes);

        $articleIds = array_map(
            static fn (DomainArticle $article): int => $article->getIdValue(),
            $result->articles,
        );
        $articleUuids = array_map(
            static fn (DomainArticle $article): string => $article->getUid()->value(),
            $result->articles,
        );

        $statsMap = $includes->includeStats
            ? $this->engagementService->getArticleStatsByIds($articleIds)
            : [];

        $hashtagsMap = $includes->includeHashtags
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
        $facets = $includes->includeFacets
            ? $this->articleListReader->facets($criteria, $scope)
            : [];

        return new ArticleListResultDTO(
            items: $items,
            pagination: $result->pagination,
            includes: $includes,
            facets: $facets,
            query: $criteria->toCanonicalArray(),
        );
    }
}
