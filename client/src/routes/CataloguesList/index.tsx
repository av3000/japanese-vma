import React, { useMemo, useState } from 'react';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import Spinner from '@/assets/images/spinner.gif';
import { CatalogueCard } from '@/components/features/catalogues/CatalogueCard/CatalogueCard';
import {
	CatalogueFilters,
	DEFAULT_CATALOGUE_SEARCH_FILTERS,
	type CatalogueSearchFilters,
} from '@/components/features/catalogues/CatalogueFilters';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Grid, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { CATALOGUE_ROUTES, isCustomCatalogueType } from '@/shared/constants/catalogues';
import CataloguesListSkeleton from './CatalogueListSkeleton/CataloguesListSkeleton';
import styles from './CataloguesList.module.css';

export const DEFAULT_PER_PAGE = 12;

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
	const { isAuthenticated } = useAuth();
	// `draft` is what the form edits; `filters` is what the last submit applied.
	const [draft, setDraft] = useState<CatalogueSearchFilters>(DEFAULT_CATALOGUE_SEARCH_FILTERS);
	const [filters, setFilters] = useState<CatalogueSearchFilters | Record<string, never>>({});
	const queryFilters = useMemo(() => mapSearchFiltersToCatalogueParams(filters), [filters]);
	const { catalogues, total, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, error, isError } =
		useInfiniteCatalogues({
			filters: queryFilters,
		});

	const keyword = typeof filters.keyword === 'string' ? filters.keyword.trim() : '';

	const newCatalogueAction = isAuthenticated ? (
		<Button to={CATALOGUE_ROUTES.create} variant="primary">
			New catalogue
		</Button>
	) : undefined;

	if (isPending && catalogues.length === 0) {
		return (
			<Container className={styles.page}>
				<Stack gap="md">
					<PageHeader title="Catalogues" action={newCatalogueAction} />
					<PageLoading family="list" visual={<CataloguesListSkeleton />} />
				</Stack>
			</Container>
		);
	}

	if (isError) {
		return (
			<Container className={styles.page}>
				<Stack gap="md">
					<PageHeader title="Catalogues" action={newCatalogueAction} />
					<Alert tone="danger">Error: {error.message}</Alert>
				</Stack>
			</Container>
		);
	}

	const meta = [`Showing ${catalogues.length} of ${total}`, keyword !== '' && `Results for: ${keyword}`]
		.filter(Boolean)
		.join(' · ');

	return (
		<Container className={styles.page}>
			<Stack gap="md">
				<PageHeader title="Catalogues" meta={meta} action={newCatalogueAction} />

				<CatalogueFilters value={draft} onChange={setDraft} onSubmit={() => setFilters(draft)} />

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
