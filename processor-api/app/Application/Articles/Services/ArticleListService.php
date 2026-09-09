<?php

declare(strict_types=1);

namespace App\Application\Articles\Services;

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
use App\Shared\Results\Result;

/**
 * It owns exactly two things: deriving the mandatory visibility scope from the
 * actor, and batching enrichment around whichever reader is bound. It contains no
 * Eloquent or vendor query syntax.
 */
final readonly class ArticleListService implements ArticleListServiceInterface
{
    public function __construct(
        private ArticleListReaderInterface $articleListReader,
        private ArticleProcessingStateReaderInterface $processingStateReader,
        private ArticlePolicy $articlePolicy,
        private EngagementServiceInterface $engagementService,
        private HashtagServiceInterface $hashtagService,
    ) {
    }

    public function list(
        ArticleQueryCriteria $criteria,
        ArticleListIncludes $includes,
        ?AuthenticatedUser $actor = null,
    ): Result {
        $scope = $this->articlePolicy->scopeFor($actor);

        $page = $this->articleListReader->search($criteria, $scope, $includes);

        $articleIds = array_map(
            static fn (DomainArticle $article): int => $article->getIdValue(),
            $page->articles,
        );
        $articleUuids = array_map(
            static fn (DomainArticle $article): string => $article->getUid()->value(),
            $page->articles,
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
            $page->articles,
        );

        // Counted from the same scope the items came from. If these were built from a
        // separately derived predicate they could drift, and a drifting count is how a
        // private Article leaks: the row stays hidden but still shows up in "N2 (13)".
        $facets = $includes->includeFacets
            ? $this->articleListReader->facets($criteria, $scope)
            : [];

        return Result::success(new ArticleListResultDTO(
            items: $items,
            pagination: $page->pagination,
            includes: $includes,
            facets: $facets,
            query: $criteria->toCanonicalArray(),
        ));
    }
}
