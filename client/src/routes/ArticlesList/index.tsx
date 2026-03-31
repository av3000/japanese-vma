import React, { useDeferredValue, useMemo, useState } from 'react';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import ArticleCard from '@/components/shared/ArticleCard';
import { Button } from '@/components/shared/Button';

const DEFAULT_PER_PAGE = 12;

type ArticleSearchFilters = {
	keyword: string;
	sortByWhat: string;
	filterType: string;
};

const mapSearchFiltersToArticleParams = (filters: Record<string, unknown>) => ({
	search: typeof filters.keyword === 'string' && filters.keyword.trim() ? filters.keyword.trim() : undefined,
	category:
		typeof filters.filterType === 'string' && filters.filterType !== '20' ? Number(filters.filterType) : undefined,
	sort_by: filters.sortByWhat === 'pop' ? 'views_total' : 'created_at',
	sort_dir: 'desc',
	per_page: DEFAULT_PER_PAGE,
	include_stats_counts: true,
	include_hashtags: true,
	include_kanjis: true,
});

const ArticleList: React.FC = () => {
	const [filters, setFilters] = useState<ArticleSearchFilters | Record<string, never>>({});
	const queryFilters = useMemo(() => mapSearchFiltersToArticleParams(filters), [filters]);
	const { articles, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteArticles({
			filters: queryFilters,
		});

	const trackedArticleUuids = useMemo(() => {
		return articles
			.filter(
				(article) =>
					article.processing_status?.status !== undefined &&
					article.processing_status?.status !== LastOperationStatus.Completed,
			)
			.map((article) => article.uuid);
	}, [articles]);
	const deferredTrackedArticleUuids = useDeferredValue(trackedArticleUuids);

	const handleApplyFilters = (newFilters: ArticleSearchFilters) => {
		setFilters(newFilters);
	};

	const searchHeading = typeof filters.keyword === 'string' && filters.keyword ? `Results for: ${filters.keyword}` : '';

	if (isPending && articles.length === 0) {
		return (
			<div className="text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (isError) {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<div className="container">
			{deferredTrackedArticleUuids.map((uuid) => (
				<ArticleSubscription key={uuid} uuid={uuid} />
			))}
			<SearchBar fetchQuery={handleApplyFilters} searchType="articles" />

			{searchHeading && <h4>{searchHeading}</h4>}
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
