import React, { useMemo, useState } from 'react';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import Spinner from '@/assets/images/spinner.gif';
import SearchBar from '@/components/features/SearchBar';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard/CatalogueCard';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Grid, Stack } from '@/components/shared/layout';
import { isCustomCatalogueType } from '@/shared/constants/catalogues';
import CataloguesListSkeleton from './CatalogueListSkeleton/CataloguesListSkeleton';
import styles from './CataloguesList.module.css';

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
		return (
			<Container className={styles.page}>
				<Alert tone="danger">Error: {error.message}</Alert>
			</Container>
		);
	}

	return (
		<Container className={styles.page}>
			<Stack gap="md">
				<SearchBar fetchQuery={setFilters} searchType="lists" />

				{searchHeading && <h4 className={styles.heading}>{searchHeading}</h4>}
				<p className={styles.summary}>
					Showing {catalogues.length} of {total}
				</p>

				{catalogues.length === 0 ? (
					<p>No catalogues found.</p>
				) : (
					<Grid as="ul" columns={12} gap="lg" className={styles.cards}>
						{catalogues.map((catalogue) => (
							<Grid.Item as="li" key={catalogue.uuid} span={{ base: 6, sm: 4, md: 3 }}>
								<CatalogueCard catalogue={catalogue} />
							</Grid.Item>
						))}
					</Grid>
				)}

				<div className={styles.pager}>
					{isFetchingNextPage ? (
						<img src={Spinner} alt="Loading more..." style={{ height: '40px' }} />
					) : hasNextPage ? (
						<Button variant="secondary-outline" className={styles.loadMore} onClick={() => fetchNextPage()}>
							Load More
						</Button>
					) : (
						<span className={styles.muted}>No more results</span>
					)}
				</div>
			</Stack>
		</Container>
	);
};

export default CataloguesListPage;
