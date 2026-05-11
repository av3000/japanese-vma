import React, { useCallback, useMemo, useState } from 'react';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
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

export const mapDashboardSearchFiltersToCatalogueFilters = (filters: SearchFilters): DashboardCatalogueFilters => {
	const keyword = filters.keyword.trim();
	const parsedType = Number(filters.filterType);

	return {
		search: keyword || undefined,
		sort_by: filters.sortByWhat === 'pop' ? 'views' : 'created_at',
		sort_dir: 'desc',
		type: LIST_FILTER_TYPES.has(parsedType) ? parsedType : undefined,
	};
};

const DashboardCataloguesPanel: React.FC<DashboardCataloguesPanelProps> = ({ isAuthenticated, currentUser }) => {
	const [filters, setFilters] = useState<DashboardCatalogueFilters>({
		sort_by: 'created_at',
		sort_dir: 'desc',
	});

	const queryFilters = useMemo(
		() => ({
			owner_uid: currentUser?.uuid,
			search: filters.search,
			sort_by: filters.sort_by,
			sort_dir: filters.sort_dir,
			type: filters.type,
			public_only: false,
			custom_only: false,
			include_stats_counts: true,
			include_hashtags: true,
		}),
		[currentUser?.uuid, filters],
	);

	const { catalogues, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, isError } =
		useInfiniteCatalogues({
			filters: queryFilters,
			enabled: isAuthenticated && !!currentUser?.uuid,
		});

	const handleFilterResults = useCallback((newFilters: SearchFilters) => {
		setFilters(mapDashboardSearchFiltersToCatalogueFilters(newFilters));
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
						Showing {catalogues.length} of {total}
					</div>
					{isPending ? (
						<LoadingState altText="Loading lists..." />
					) : isError ? (
						<div className="alert alert-danger">{errorMessage}</div>
					) : catalogues.length ? (
						<>
							{catalogues.map((catalogue) => (
								<DashboardListItem key={catalogue.id} {...catalogue} />
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
