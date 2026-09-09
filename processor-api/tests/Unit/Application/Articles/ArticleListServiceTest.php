<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Articles;

use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Articles\Services\ArticleListService;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Articles\DTOs\ArticleListIncludes;
use App\Domain\Articles\DTOs\ArticlePageDTO;
use App\Domain\Articles\DTOs\ArticlePaginationDTO;
use App\Domain\Articles\Queries\ArticleQueryCriteria;
use App\Domain\Articles\ValueObjects\ArticleSortCriteria;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\ValueObjects\Pagination;
use PHPUnit\Framework\TestCase;

class ArticleListServiceTest extends TestCase
{
    /**
     * The security-critical delegation: the reader must receive exactly the scope the
     * policy derived from the actor. If the action could build its own scope, or pass
     * anything influenced by the query, request input could widen access.
     */
    public function test_it_passes_the_policy_derived_scope_to_the_reader(): void
    {
        $scope = ArticleVisibilityScope::publicOrOwnedBy(7);

        $policy = $this->createMock(ArticlePolicy::class);
        $policy->expects($this->once())
            ->method('scopeFor')
            ->with(null)
            ->willReturn($scope);

        $criteria = $this->criteria();
        $includes = ArticleListIncludes::itemsOnly();

        $reader = $this->createMock(ArticleListReaderInterface::class);
        $reader->expects($this->once())
            ->method('search')
            ->with($criteria, $this->identicalTo($scope), $includes)
            ->willReturn($this->emptyReadResult());

        $service = new ArticleListService(
            $reader,
            $this->neverCalledProcessingStateReader(),
            $policy,
            $this->neverCalledEngagementService(),
            $this->neverCalledHashtagService(),
        );

        $service->list($criteria, $includes);
    }

    public function test_it_skips_enrichment_the_includes_did_not_ask_for(): void
    {
        $engagement = $this->createMock(EngagementServiceInterface::class);
        $engagement->expects($this->never())->method('getArticleStatsByIds');

        $hashtags = $this->createMock(HashtagServiceInterface::class);
        $hashtags->expects($this->never())->method('getBatchHashtags');

        $service = new ArticleListService(
            $this->readerReturningEmptyPage(),
            $this->neverCalledProcessingStateReader(),
            $this->policyReturning(ArticleVisibilityScope::publicOnly()),
            $engagement,
            $hashtags,
        );

        $service->list($this->criteria(), ArticleListIncludes::itemsOnly());
    }

    public function test_it_returns_the_reader_pagination_and_the_requested_includes(): void
    {
        $includes = new ArticleListIncludes(includeStats: false, includeHashtags: false);

        $service = new ArticleListService(
            $this->readerReturningEmptyPage(),
            $this->neverCalledProcessingStateReader(),
            $this->policyReturning(ArticleVisibilityScope::unrestricted()),
            $this->neverCalledEngagementService(),
            $this->neverCalledHashtagService(),
        );

        $page = $service->list($this->criteria(), $includes)->getData();

        $this->assertSame([], $page->items);
        $this->assertSame($includes, $page->includes);
        $this->assertSame(1, $page->pagination->page);
        $this->assertSame(20, $page->pagination->perPage);
        $this->assertSame(0, $page->pagination->total);
        $this->assertFalse($page->pagination->hasMore);
    }

    /**
     * Related-Article panels never render processing_status, so they must not pay
     * the last_operations query for it.
     */
    public function test_it_skips_processing_state_when_not_included(): void
    {
        $processingStates = $this->createMock(ArticleProcessingStateReaderInterface::class);
        $processingStates->expects($this->never())->method('latestKanjiExtractionStates');

        // relatedPanel() still wants stats and hashtags; only processing state is off.
        $engagement = $this->createMock(EngagementServiceInterface::class);
        $engagement->method('getArticleStatsByIds')->willReturn([]);
        $hashtags = $this->createMock(HashtagServiceInterface::class);
        $hashtags->method('getBatchHashtags')->willReturn([]);

        $service = new ArticleListService(
            $this->readerReturningEmptyPage(),
            $processingStates,
            $this->policyReturning(ArticleVisibilityScope::publicOnly()),
            $engagement,
            $hashtags,
        );

        $service->list($this->criteria(), ArticleListIncludes::relatedPanel());
    }

    public function test_list_items_reads_rows_without_a_total_and_returns_only_items(): void
    {
        $scope = ArticleVisibilityScope::unrestricted();
        $criteria = $this->criteria();
        $includes = ArticleListIncludes::itemsOnly();

        $reader = $this->createMock(ArticleListReaderInterface::class);
        $reader->expects($this->never())->method('search');
        $reader->expects($this->never())->method('facets');
        $reader->expects($this->once())
            ->method('listWithoutTotal')
            ->with($criteria, $this->identicalTo($scope), $includes)
            ->willReturn([]);

        $service = new ArticleListService(
            $reader,
            $this->neverCalledProcessingStateReader(),
            $this->policyReturning($scope),
            $this->neverCalledEngagementService(),
            $this->neverCalledHashtagService(),
        );

        $result = $service->listItems($criteria, $includes);

        $this->assertTrue($result->isSuccess());
        $this->assertSame([], $result->getData());
    }

    private function criteria(): ArticleQueryCriteria
    {
        return new ArticleQueryCriteria(
            sort: ArticleSortCriteria::default(),
            pagination: Pagination::default(),
        );
    }

    private function emptyReadResult(): ArticlePageDTO
    {
        return new ArticlePageDTO(
            articles: [],
            pagination: new ArticlePaginationDTO(page: 1, perPage: 20, total: 0, lastPage: 1, hasMore: false),
        );
    }

    private function readerReturningEmptyPage(): ArticleListReaderInterface
    {
        $reader = $this->createMock(ArticleListReaderInterface::class);
        $reader->method('search')->willReturn($this->emptyReadResult());

        return $reader;
    }

    private function policyReturning(ArticleVisibilityScope $scope): ArticlePolicy
    {
        $policy = $this->createMock(ArticlePolicy::class);
        $policy->method('scopeFor')->willReturn($scope);

        return $policy;
    }

    private function neverCalledEngagementService(): EngagementServiceInterface
    {
        $engagement = $this->createMock(EngagementServiceInterface::class);
        $engagement->expects($this->never())->method('getArticleStatsByIds');

        return $engagement;
    }

    private function neverCalledHashtagService(): HashtagServiceInterface
    {
        $hashtags = $this->createMock(HashtagServiceInterface::class);
        $hashtags->expects($this->never())->method('getBatchHashtags');

        return $hashtags;
    }

    private function neverCalledProcessingStateReader(): ArticleProcessingStateReaderInterface
    {
        $reader = $this->createMock(ArticleProcessingStateReaderInterface::class);
        $reader->method('latestKanjiExtractionStates')->willReturn([]);

        return $reader;
    }
}
