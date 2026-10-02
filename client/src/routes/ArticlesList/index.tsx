import React, { useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import type { ArticleIndexJlptLevelsItem } from '@/api/generated/model/articleIndexJlptLevelsItem';
import type { ArticleIndexSort } from '@/api/generated/model/articleIndexSort';
import Spinner from '@/assets/images/spinner.gif';
import ArticleCard from '@/components/features/LibraryCards/ArticleCard';
import { LibraryCardGrid, LibraryEmptyState, LibraryPage } from '@/components/features/LibraryCards/LibraryLayout';
import ArticleFilters from '@/components/features/articles/ArticleFilters';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
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

/** What the empty list says: a search, other filters, or no articles at all. */
const emptyState = (state: ArticleListFilterState) => {
	if (state.q !== '') {
		return { title: 'No articles match', term: state.q, hint: 'Try a shorter search, or clear the filters.' };
	}

	if (state.jlptLevels.length > 0 || state.hashtagIds.length > 0) {
		return { title: 'No articles match these filters', hint: 'Try fewer filters, or clear them.' };
	}

	return { title: 'No articles yet', hint: 'Articles you and others publish appear here.' };
};

/**
 * Article discovery.
 *
 * The URL owns every filter. The route reads state out of it, maps that onto
 * generated request parameters, and writes state back on every change - so refresh,
 * deep links and browser back/forward all reproduce exactly what is on screen.
 */
const ArticleList: React.FC = () => {
	const [searchParams, setSearchParams] = useSearchParams();
	const { isAuthenticated } = useAuth();

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

	const newArticleAction = isAuthenticated ? (
		<Button to="/newarticle" variant="primary">
			New article
		</Button>
	) : undefined;

	if (isPending && articles.length === 0) {
		return (
			<LibraryPage>
				<PageHeader title="Articles" action={newArticleAction} />
				<PageLoading family="list" visual={<ArticlesListSkeleton />} />
			</LibraryPage>
		);
	}

	if (isError) {
		return (
			<LibraryPage>
				<PageHeader title="Articles" action={newArticleAction} />
				<Alert tone="danger">Error: {error.message}</Alert>
			</LibraryPage>
		);
	}

	const meta = [`Showing ${articles.length} of ${total}`, filterState.q !== '' && `Results for: ${filterState.q}`]
		.filter(Boolean)
		.join(' · ');

	const empty = emptyState(filterState);

	return (
		// No per-article sockets here (#263): the polling fallback keeps badges current.
		<LibraryPage>
			<PageHeader title="Articles" meta={meta} action={newArticleAction} />

			<ArticleFilters
				state={filterState}
				facets={facets}
				onSearch={handleSearch}
				onToggleJlptLevel={handleToggleJlptLevel}
				onToggleHashtag={handleToggleHashtag}
				onSortChange={handleSortChange}
				onReset={handleReset}
			/>

			{articles.length === 0 ? (
				<LibraryEmptyState {...empty} />
			) : (
				<LibraryCardGrid>
					{articles.map((article) => (
						<ArticleCard key={article.id} article={article} />
					))}
				</LibraryCardGrid>
			)}

			{/* The empty state already says there is nothing; "No more results" under it is noise. */}
			{articles.length > 0 && (
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
			)}
		</LibraryPage>
	);
};

export default ArticleList;
