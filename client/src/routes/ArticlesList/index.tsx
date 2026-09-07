import React, { useCallback, useDeferredValue, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import Spinner from '@/assets/images/spinner.gif';
import ArticleFilters from '@/components/features/articles/ArticleFilters';
import ArticleCard from '@/components/shared/ArticleCard';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import ArticlesListSkeleton from './ArticlesListSkeleton/ArticlesListSkeleton';
import {
	mapArticleFiltersToGeneratedParams,
	parseArticleListSearchParams,
	resetPage,
	serializeArticleListFilterState,
	toggleHashtagId,
	toggleJlptLevel,
	type ArticleListFilterState,
} from './articleListSearchParams';

/**
 * Article discovery.
 *
 * The URL owns every filter. The route reads state out of it, maps that onto
 * generated request parameters, and writes state back on every change - so refresh,
 * deep links and browser back/forward all reproduce exactly what is on screen.
 */
const ArticleList: React.FC = () => {
	const [searchParams, setSearchParams] = useSearchParams();

	const filterState = useMemo(() => parseArticleListSearchParams(searchParams), [searchParams]);

	// This route renders the facet controls, so it is the one caller that pays for them.
	const queryFilters = useMemo(
		() => mapArticleFiltersToGeneratedParams(filterState, { includeFacets: true }),
		[filterState],
	);

	const { articles, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError, data } =
		useInfiniteArticles({ filters: queryFilters });

	// Facets describe the whole result set, not one page, so the first page is
	// authoritative and later pages cannot disagree with it.
	const facets = data?.pages?.[0]?.facets ?? [];

	const applyState = useCallback(
		(next: ArticleListFilterState) => {
			setSearchParams(serializeArticleListFilterState(next));
		},
		[setSearchParams],
	);

	const handleSearch = useCallback(
		(q: string) => applyState(resetPage({ ...filterState, q })),
		[applyState, filterState],
	);

	const handleToggleJlptLevel = useCallback(
		(level: ArticleIndexJlptLevelsItem) => applyState(toggleJlptLevel(filterState, level)),
		[applyState, filterState],
	);

	const handleToggleHashtag = useCallback(
		(hashtagId: number) => applyState(toggleHashtagId(filterState, hashtagId)),
		[applyState, filterState],
	);

	const handleSortChange = useCallback(
		(sort: ArticleIndexSort) => applyState(resetPage({ ...filterState, sort })),
		[applyState, filterState],
	);

	const handleReset = useCallback(() => setSearchParams(new URLSearchParams()), [setSearchParams]);

	const trackedArticleUuids = useMemo(
		() =>
			articles
				.filter(
					(article) =>
						article.processing_status?.status !== undefined &&
						article.processing_status?.status !== LastOperationStatus.completed,
				)
				.map((article) => article.uuid),
		[articles],
	);
	const deferredTrackedArticleUuids = useDeferredValue(trackedArticleUuids);

	if (isPending && articles.length === 0) {
		return <PageLoading family="list" visual={<ArticlesListSkeleton />} />;
	}

	if (isError) {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<div className="container">
			{deferredTrackedArticleUuids.map((uuid) => (
				<ArticleSubscription key={uuid} uuid={uuid} />
			))}

			<ArticleFilters
				state={filterState}
				facets={facets}
				onSearch={handleSearch}
				onToggleJlptLevel={handleToggleJlptLevel}
				onToggleHashtag={handleToggleHashtag}
				onSortChange={handleSortChange}
				onReset={handleReset}
			/>

			{filterState.q !== '' && <h4>Results for: {filterState.q}</h4>}

			<div className="mb-3 text-muted">
				Showing {articles.length} of {total}
			</div>

			<div className="row">
				{articles.length === 0 ? (
					<p>No articles found.</p>
				) : (
					<>
						{articles.map((article) => (
							<div key={article.id} className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
								<ArticleCard article={article} />
							</div>
						))}
					</>
				)}
			</div>

			<div className="row justify-content-center mt-4 mb-5">
				{isFetchingNextPage ? (
					<img src={Spinner} alt="Loading more..." style={{ height: '40px' }} />
				) : hasNextPage ? (
					<Button variant="secondary-outline" className="w-50" onClick={() => fetchNextPage()}>
						Load More
					</Button>
				) : (
					<span className="text-muted">No more results</span>
				)}
			</div>
		</div>
	);
};

const ArticleSubscription: React.FC<{ uuid: string }> = ({ uuid }) => {
	useArticleSubscription(uuid);
	return null;
};

export default ArticleList;
