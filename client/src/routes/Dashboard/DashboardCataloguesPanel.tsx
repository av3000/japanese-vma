import React, { useMemo, useState } from 'react';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import Spinner from '@/assets/images/spinner.gif';
import {
	CatalogueFilters,
	DEFAULT_CATALOGUE_SEARCH_FILTERS,
	type CatalogueSearchFilters,
} from '@/components/features/catalogues/CatalogueFilters';
import DashboardListItem from '@/components/features/dashboard/DashboardListItem';
import dashboardRowStyles from '@/components/features/dashboard/DashboardRow.module.css';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import { useDebouncedValue } from '@/hooks/useDebouncedValue';
import { isCustomCatalogueType } from '@/shared/constants/catalogues';
import type { User } from '@/types';
import styles from './Dashboard.module.css';

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

/** How long the dashboard waits after the last keystroke before it searches. */
const SEARCH_DEBOUNCE_MS = 300;

export const mapDashboardSearchFiltersToCatalogueFilters = (
	filters: CatalogueSearchFilters,
): DashboardCatalogueFilters => {
	const keyword = filters.keyword.trim();
	const parsedType = Number(filters.filterType);

	return {
		search: keyword || undefined,
		sort_by: filters.sortByWhat === 'pop' ? 'views' : 'created_at',
		sort_dir: 'desc',
		type: isCustomCatalogueType(parsedType) ? parsedType : undefined,
	};
};

const DashboardCataloguesPanel: React.FC<DashboardCataloguesPanelProps> = ({ isAuthenticated, currentUser }) => {
	// The dashboard searches as the user types; Enter or the button catches up at once.
	const [draft, setDraft] = useState<CatalogueSearchFilters>(DEFAULT_CATALOGUE_SEARCH_FILTERS);
	const [searchFilters, applyDraft] = useDebouncedValue(draft, SEARCH_DEBOUNCE_MS);
	const filters = useMemo(() => mapDashboardSearchFiltersToCatalogueFilters(searchFilters), [searchFilters]);

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

	const errorMessage = error instanceof Error ? error.message : 'Failed to load lists.';

	return (
		<Stack gap="md">
			<div className={styles.toolbar}>
				<CatalogueFilters value={draft} onChange={setDraft} onSubmit={applyDraft} />
			</div>

			<Stack as="section" gap="md" className={styles.panel}>
				<Cluster justify="between">
					<h4 className={`${styles.panelTitle} ${styles.panelTitleUnderlined}`}>My Lists</h4>
				</Cluster>
				<p className={styles.summary}>
					Showing {catalogues.length} of {total}
				</p>
				{isPending ? (
					<LoadingState altText="Loading lists..." />
				) : isError ? (
					<Alert tone="danger">{errorMessage}</Alert>
				) : catalogues.length ? (
					<>
						<ul className={dashboardRowStyles.list}>
							{catalogues.map((catalogue) => (
								<DashboardListItem key={catalogue.id} {...catalogue} />
							))}
						</ul>
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
								<span className={styles.label}>No more results</span>
							)}
						</Cluster>
					</>
				) : (
					<Alert tone="info" className={styles.emptyState}>
						You have no Lists yet.
					</Alert>
				)}
			</Stack>
		</Stack>
	);
};

const LoadingState: React.FC<{ altText: string }> = ({ altText }) => (
	<Cluster justify="center" className={styles.loading}>
		<img src={Spinner} alt={altText} />
	</Cluster>
);

export default DashboardCataloguesPanel;
