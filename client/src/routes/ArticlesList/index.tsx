// // @ts-nocheck
// /* eslint-disable */
import React, { useEffect, useMemo, useState } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { fetchArticles } from '@/api/articles/articles';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import ArticleCard from '@/components/shared/ArticleCard';
import { Button } from '@/components/shared/Button';

const ArticleList: React.FC = () => {
	const [filters, setFilters] = useState<Record<string, any>>({});

	const { data, error, fetchNextPage, hasNextPage, isFetchingNextPage, status } = useInfiniteQuery({
		queryKey: ['articles', filters],
		queryFn: ({ pageParam }) => fetchArticles(filters, pageParam),
		initialPageParam: 1,
		getNextPageParam: (lastPage) => {
			return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
		},
	});

	const handleApplyFilters = (newFilters: any) => {
		setFilters(newFilters);
	};

	const allArticles = useMemo(() => data?.pages.flatMap((page) => page.items) || [], [data?.pages]);
	const totalCount = data?.pages[0]?.pagination.total || 0;

	const trackedArticleUuids = useMemo(() => {
		return allArticles
				.filter(
					(article) =>
						article.processing_status?.status !== undefined &&
						article.processing_status?.status !== LastOperationStatus.Completed,
				)
				.map((article) => article.uuid);
		}, [allArticles]);

	const [debouncedTrackedUuids, setDebouncedTrackedUuids] = useState<string[]>([]);

	useEffect(() => {
		const timeout = window.setTimeout(() => {
			setDebouncedTrackedUuids(trackedArticleUuids);
		}, 300);

		return () => window.clearTimeout(timeout);
	}, [trackedArticleUuids]);

	const searchHeading = filters.title ? `Results for: ${filters.title}` : '';

	if (status === 'pending') {
		return (
			<div className="text-center mt-5">
				<img src={Spinner} alt="Loading..." />
			</div>
		);
	}

	if (status === 'error') {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<div className="container">
			{debouncedTrackedUuids.map((uuid) => (
				<ArticleSubscription key={uuid} uuid={uuid} />
			))}
			<SearchBar fetchQuery={handleApplyFilters} searchType="articles" />

			{searchHeading && <h4>{searchHeading}</h4>}
			<div className="mb-3 text-muted">
				Showing {allArticles.length} of {totalCount}
			</div>

			<div className="row">
				{allArticles.length === 0 ? (
					<p>No articles found.</p>
				) : (
					<>
						{allArticles.map((article) => (
							<div key={article.id} className="col-lg-3 col-md-4 col-sm-6 col-6 mb-4">
								<ArticleCard article={article} />
							</div>
						))}
					</>
				)}
			</div>
			{/* TODO: Should be shared UI component */}
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
