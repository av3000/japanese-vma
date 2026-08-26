import React, { useMemo, useState } from 'react';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard/CatalogueCard';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { isCustomCatalogueType } from '@/shared/constants/catalogues';
import CataloguesListSkeleton from './CatalogueListSkeleton/CataloguesListSkeleton';

export const DEFAULT_PER_PAGE = 12;

type CatalogueSearchFilters = {
	keyword: string;
	sortByWhat: string;
	filterType: string;
};

export const mapSearchFiltersToCatalogueParams = (
	filters: CatalogueSearchFilters | Record<string, never>,
): FetchCataloguesFilters => {
	const parsedType = typeof filters.filterType === 'string' ? Number(filters.filterType) : NaN;

	return {
		search: typeof filters.keyword === 'string' && filters.keyword.trim() ? filters.keyword.trim() : undefined,
		sort_by: filters.sortByWhat === 'pop' ? 'views' : 'created_at',
		sort_dir: 'desc',
		type: isCustomCatalogueType(parsedType) ? parsedType : undefined,
		per_page: DEFAULT_PER_PAGE,
		public_only: true,
		custom_only: true,
		include_stats_counts: true,
		include_hashtags: true,
	};
};

const CataloguesListPage: React.FC = () => {
	const [filters, setFilters] = useState<CatalogueSearchFilters | Record<string, never>>({});
	const queryFilters = useMemo(() => mapSearchFiltersToCatalogueParams(filters), [filters]);
	const { catalogues, total, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, error, isError } =
		useInfiniteCatalogues({
			filters: queryFilters,
		});

	const searchHeading =
		typeof filters.keyword === 'string' && filters.keyword.trim() ? `Results for: ${filters.keyword.trim()}` : '';

	if (isPending && catalogues.length === 0) {
		return <PageLoading family="list" visual={<CataloguesListSkeleton />} />;
	}

	if (isError) {
		return <div className="text-danger">Error: {error.message}</div>;
	}

	return (
		<div className="container">
			<SearchBar fetchQuery={setFilters} searchType="lists" />

			{searchHeading && <h4>{searchHeading}</h4>}
			<div className="mb-3 text-muted">
				Showing {catalogues.length} of {total}
			</div>

			<div className="row">
				{catalogues.length === 0 ? (
					<p>No catalogues found.</p>
				) : (
					catalogues.map((catalogue) => <CatalogueCard key={catalogue.uuid} catalogue={catalogue} />)
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

export default CataloguesListPage;
