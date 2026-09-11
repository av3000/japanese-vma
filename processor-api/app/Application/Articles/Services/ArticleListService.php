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

        // Counted from the same scope the items came from. If these were built from a
        // separately derived predicate they could drift, and a drifting count is how a
        // private Article leaks: the row stays hidden but still shows up in "N2 (13)".
        $facets = $includes->includeFacets
            ? $this->articleListReader->facets($criteria, $scope)
            : [];

        return Result::success(new ArticleListResultDTO(
            items: $this->enrich($page->articles, $includes),
            pagination: $page->pagination,
            includes: $includes,
            criteria: $criteria,
            facets: $facets,
        ));
    }

    public function listItems(
        ArticleQueryCriteria $criteria,
        ArticleListIncludes $includes,
        ?AuthenticatedUser $actor = null,
    ): Result {
        $scope = $this->articlePolicy->scopeFor($actor);

        $articles = $this->articleListReader->listWithoutTotal($criteria, $scope, $includes);

        return Result::success($this->enrich($articles, $includes));
    }

    /**
     * Batched enrichment: one query per include, independent of page size.
     *
     * @param array<int, DomainArticle> $articles
     *
     * @return array<int, ArticleListItemDTO>
     */
    private function enrich(array $articles, ArticleListIncludes $includes): array
    {
        $articleIds = array_map(
            static fn (DomainArticle $article): int => $article->getIdValue(),
            $articles,
        );

        $statsMap = $includes->includeStats
            ? $this->engagementService->getArticleStatsByIds($articleIds)
            : [];

        $hashtagsMap = $includes->includeHashtags
            ? $this->hashtagService->getBatchHashtags($articleIds, ObjectTemplateType::ARTICLE)
            : [];

        $processingStates = $includes->includeProcessingState
            ? $this->processingStateReader->latestKanjiExtractionStates(array_map(
                static fn (DomainArticle $article): string => $article->getUid()->value(),
                $articles,
            ))
            : [];

        return array_map(
            static fn (DomainArticle $article): ArticleListItemDTO => new ArticleListItemDTO(
                article: $article,
                stats: $statsMap[$article->getIdValue()] ?? null,
                hashtags: $hashtagsMap[$article->getIdValue()] ?? [],
                processingState: $processingStates[$article->getUid()->value()] ?? null,
            ),
            $articles,
        );
    }
}
