import React, { useCallback, useMemo, useState } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { fetchCatalogues } from '@/api/catalogues/catalogues';
import type { Catalogue } from '@/api/catalogues/catalogues';
import Spinner from '@/assets/images/spinner.gif';
import DashboardListItem from '@/components/features/dashboard/DashboardListItem';
import { Button } from '@/components/shared/Button';
import type { User } from '@/types';
import SearchBarDashboard from './SearchBarDashboard';
import type { SearchFilters } from './SearchBarDashboard';

type DashboardCatalogueFilters = {
	search?: string;
	// TODO: filter keys should be from shared generic consts list for common filtering
	sort_by: 'created_at' | 'views';
	sort_dir: 'desc';
	type?: number;
};

interface DashboardCataloguesPanelProps {
	isAuthenticated: boolean;
	currentUser: User | null;
}

// TODO: these not supposed to be magical numbers, use some const typed values
const LIST_FILTER_TYPES = new Set([5, 6, 7, 8, 9]);

const DashboardCataloguesPanel: React.FC<DashboardCataloguesPanelProps> = ({ isAuthenticated, currentUser }) => {
	const [filters, setFilters] = useState<DashboardCatalogueFilters>({
		sort_by: 'created_at',
		sort_dir: 'desc',
	});

	const { data, error, fetchNextPage, hasNextPage, isFetchingNextPage, status } = useInfiniteQuery({
		queryKey: [
			'catalogues',
			{
				scope: 'dashboard',
				owner_uid: currentUser?.uuid ?? null,
				search: filters.search ?? null,
				sort_by: filters.sort_by,
				sort_dir: filters.sort_dir,
				type: filters.type ?? null,
				public_only: false,
				custom_only: false,
			},
		],
		queryFn: ({ pageParam }) => {
			if (!currentUser?.uuid) {
				throw new Error('Missing current user UUID');
			}

			return fetchCatalogues(
				{
					owner_uid: currentUser.uuid,
					search: filters.search,
					sort_by: filters.sort_by,
					sort_dir: filters.sort_dir,
					type: filters.type,
					public_only: false,
					custom_only: false,
					include_stats_counts: true,
					include_hashtags: true,
				},
				pageParam,
			);
		},
		initialPageParam: 1,
		getNextPageParam: (lastPage) => {
			return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
		},
		enabled: isAuthenticated && !!currentUser?.uuid,
	});

	const allCatalogues = useMemo<Catalogue[]>(() => data?.pages.flatMap((page) => page.items) ?? [], [data?.pages]);
	const totalCount = data?.pages[0]?.pagination.total ?? 0;

	const handleFilterResults = useCallback((newFilters: SearchFilters) => {
		const keyword = newFilters.keyword.trim();
		const mappedSort = newFilters.sortByWhat === 'pop' ? 'views' : 'created_at';
		const parsedType = Number(newFilters.filterType);

		setFilters({
			search: keyword || undefined,
			sort_by: mappedSort,
			sort_dir: 'desc',
			type: LIST_FILTER_TYPES.has(parsedType) ? parsedType : undefined,
		});
	}, []);

	const errorMessage = error instanceof Error ? error.message : 'Failed to load lists.';

	return (
		<>
			<div className="ml-3 mt-2">
				<SearchBarDashboard searchType="lists" filterResults={handleFilterResults} />
			</div>

			<div className="my-3 p-3 bg-white rounded box-shadow">
				<div className="d-flex justify-content-between align-items-center mb-3">
					<h4 className="border-bottom border-gray pb-2 mb-0">My Lists</h4>
				</div>
				<div className="col-lg-12 col-md-10 mx-auto">
					<div className="mb-3 text-muted">
						Showing {allCatalogues.length} of {totalCount}
					</div>
					{status === 'pending' ? (
						<LoadingState altText="Loading lists..." />
					) : status === 'error' ? (
						<div className="alert alert-danger">{errorMessage}</div>
					) : allCatalogues.length ? (
						<>
							{allCatalogues.map((catalogue) => (
								<DashboardListItem
									key={catalogue.id}
									id={catalogue.id}
									created_at={catalogue.created_at}
									title={catalogue.title}
									commentsTotal={catalogue.engagement?.comments_count ?? 0}
									likesTotal={catalogue.engagement?.likes_count ?? 0}
									viewsTotal={catalogue.engagement?.views_count ?? 0}
									hashtags={catalogue.hashtags ?? []}
									typeTitle={catalogue.type_label}
								/>
							))}
							<div className="row justify-content-center mt-4 mb-2">
								{isFetchingNextPage ? (
									<img src={Spinner} alt="Loading more..." style={{ height: '40px' }} />
								) : hasNextPage ? (
									<Button
										variant="secondary-outline"
										className="w-50"
										onClick={() => fetchNextPage()}
									>
										Load More
									</Button>
								) : (
									<span className="text-muted">No more results</span>
								)}
							</div>
						</>
					) : (
						<div className="alert text-center alert-info">You have no Lists yet.</div>
					)}
				</div>
			</div>
		</>
	);
};

const LoadingState: React.FC<{ altText: string }> = ({ altText }) => (
	<div className="container mt-5">
		<div className="row justify-content-center">
			<img src={Spinner} alt={altText} />
		</div>
	</div>
);

export default DashboardCataloguesPanel;
