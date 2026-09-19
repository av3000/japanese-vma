import React, { useCallback, useDeferredValue, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import Spinner from '@/assets/images/spinner.gif';
import ArticleFilters from '@/components/features/articles/ArticleFilters';
import { Alert } from '@/components/shared/Alert';
import ArticleCard from '@/components/shared/ArticleCard';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Grid, Stack } from '@/components/shared/layout';
import styles from './ArticlesList.module.css';
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
	// Known cost: include_facets is part of the infinite query key, so every Load More
	// page recomputes both facet counts server-side and only pages[0] is read below.
	// Two bounded queries per page today. Split facets into their own query keyed on
	// the filter state without `page` once hashtag volume or list traffic makes that
	// measurable.
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
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Error: {error.message}</Alert>
			</Container>
		);
	}

	return (
		<Container className={styles.page}>
			{deferredTrackedArticleUuids.map((uuid) => (
				<ArticleSubscription key={uuid} uuid={uuid} />
			))}

			<Stack gap="md">
				<ArticleFilters
					state={filterState}
					facets={facets}
					onSearch={handleSearch}
					onToggleJlptLevel={handleToggleJlptLevel}
					onToggleHashtag={handleToggleHashtag}
					onSortChange={handleSortChange}
					onReset={handleReset}
				/>

				{filterState.q !== '' && <h4 className={styles.resultsHeading}>Results for: {filterState.q}</h4>}

				<p className={styles.muted}>
					Showing {articles.length} of {total}
				</p>

				{articles.length === 0 ? (
					<p>No articles found.</p>
				) : (
					<Grid as="ul" columns={{ base: 2, sm: 3, md: 4 }} gap="lg" className={styles.list}>
						{articles.map((article) => (
							<Grid.Item as="li" span="auto" key={article.id}>
								<ArticleCard article={article} />
							</Grid.Item>
						))}
					</Grid>
				)}

				<Cluster justify="center" className={styles.loadMore}>
					{isFetchingNextPage ? (
						<img src={Spinner} alt="Loading more..." className={styles.loadMoreSpinner} />
					) : hasNextPage ? (
						<Button
							variant="secondary-outline"
							className={styles.loadMoreButton}
							onClick={() => fetchNextPage()}
						>
							Load More
						</Button>
					) : (
						<span className={styles.muted}>No more results</span>
					)}
				</Cluster>
			</Stack>
		</Container>
	);
};

const ArticleSubscription: React.FC<{ uuid: string }> = ({ uuid }) => {
	useArticleSubscription(uuid);
	return null;
};

export default ArticleList;
