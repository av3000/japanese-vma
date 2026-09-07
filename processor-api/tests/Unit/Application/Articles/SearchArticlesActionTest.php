<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Articles;

use App\Application\Articles\Actions\Retrieval\SearchArticlesAction;
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\DTOs\ArticleListReadResult;
use App\Application\Articles\DTOs\ArticlePaginationDTO;
use App\Application\Articles\Interfaces\Readers\ArticleListReaderInterface;
use App\Application\Articles\Interfaces\Readers\ArticleProcessingStateReaderInterface;
use App\Application\Articles\Policies\ArticlePolicy;
use App\Application\Engagement\Services\EngagementServiceInterface;
use App\Application\Engagement\Services\HashtagServiceInterface;
use App\Domain\Articles\ValueObjects\ArticleListSort;
use App\Domain\Articles\ValueObjects\ArticleVisibilityScope;
use App\Domain\Shared\ValueObjects\Pagination;
use PHPUnit\Framework\TestCase;

class SearchArticlesActionTest extends TestCase
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

        $query = $this->query();
        $projection = ArticleListProjection::itemsOnly();

        $reader = $this->createMock(ArticleListReaderInterface::class);
        $reader->expects($this->once())
            ->method('search')
            ->with($query, $this->identicalTo($scope), $projection)
            ->willReturn($this->emptyReadResult());

        $action = new SearchArticlesAction(
            $reader,
            $this->neverCalledProcessingStateReader(),
            $policy,
            $this->neverCalledEngagementService(),
            $this->neverCalledHashtagService(),
        );

        $action->execute($query, $projection);
    }

    public function test_it_skips_enrichment_the_projection_did_not_ask_for(): void
    {
        $engagement = $this->createMock(EngagementServiceInterface::class);
        $engagement->expects($this->never())->method('getArticleStatsByIds');

        $hashtags = $this->createMock(HashtagServiceInterface::class);
        $hashtags->expects($this->never())->method('getBatchHashtags');

        $action = new SearchArticlesAction(
            $this->readerReturningEmptyPage(),
            $this->neverCalledProcessingStateReader(),
            $this->policyReturning(ArticleVisibilityScope::publicOnly()),
            $engagement,
            $hashtags,
        );

        $action->execute($this->query(), ArticleListProjection::itemsOnly());
    }

    public function test_it_returns_the_reader_pagination_and_the_requested_projection(): void
    {
        $projection = new ArticleListProjection(includeStats: false, includeHashtags: false);

        $action = new SearchArticlesAction(
            $this->readerReturningEmptyPage(),
            $this->neverCalledProcessingStateReader(),
            $this->policyReturning(ArticleVisibilityScope::unrestricted()),
            $this->neverCalledEngagementService(),
            $this->neverCalledHashtagService(),
        );

        $page = $action->execute($this->query(), $projection);

        $this->assertSame([], $page->items);
        $this->assertSame($projection, $page->projection);
        $this->assertSame(1, $page->pagination->page);
        $this->assertSame(20, $page->pagination->perPage);
        $this->assertSame(0, $page->pagination->total);
        $this->assertFalse($page->pagination->hasMore);
    }

    private function query(): ArticleListQuery
    {
        return new ArticleListQuery(
            sort: ArticleListSort::default(),
            pagination: Pagination::default(),
        );
    }

    private function emptyReadResult(): ArticleListReadResult
    {
        return new ArticleListReadResult(
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
