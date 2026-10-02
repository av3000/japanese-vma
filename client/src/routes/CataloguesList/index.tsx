import React, { useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import Spinner from '@/assets/images/spinner.gif';
import { CatalogueCard } from '@/components/features/LibraryCards/CatalogueCard';
import { LibraryCardGrid, LibraryEmptyState, LibraryPage } from '@/components/features/LibraryCards/LibraryLayout';
import { CatalogueFilters, type CatalogueSearchFilters } from '@/components/features/catalogues/CatalogueFilters';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';
import { CATALOGUE_ROUTES, isCustomCatalogueType } from '@/shared/constants/catalogues';
import CataloguesListSkeleton from './CatalogueListSkeleton/CataloguesListSkeleton';
import styles from './CataloguesList.module.css';
import { parseCatalogueListSearchParams, serializeCatalogueListFilters } from './catalogueListSearchParams';

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
		// The cards render a JLPT bar (#388); the backend computes it only when asked.
		include_jlpt_levels: true,
	};
};

/** What the empty list says: a search, a type filter, or no public catalogues at all. */
const emptyState = (filters: CatalogueSearchFilters) => {
	const keyword = filters.keyword.trim();

	if (keyword !== '') {
		return { title: 'No catalogues match', term: keyword, hint: 'Try a shorter search, or clear the filters.' };
	}

	if (isCustomCatalogueType(Number(filters.filterType))) {
		return { title: 'No public catalogues of this type yet', hint: 'Try another type, or All.' };
	}

	return { title: 'No public catalogues yet', hint: 'Catalogues people share publicly appear here.' };
};

/**
 * Catalogue discovery.
 *
 * The URL owns the applied keyword, type and sort (#389): refresh, deep links and back/forward
 * reproduce the list. The form edits a draft; a type or sort change applies at once, along with
 * whatever keyword is typed, while the keyword alone waits for Enter or the button.
 */
const CataloguesListPage: React.FC = () => {
	const { isAuthenticated } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const appliedKey = searchParams.toString();
	const filters = useMemo(() => parseCatalogueListSearchParams(new URLSearchParams(appliedKey)), [appliedKey]);

	// The draft follows the URL whenever the URL changes (submit, back/forward), and only then.
	const [draft, setDraft] = useState<CatalogueSearchFilters>(filters);
	const [draftSource, setDraftSource] = useState(appliedKey);
	if (draftSource !== appliedKey) {
		setDraftSource(appliedKey);
		setDraft(filters);
	}

	const queryFilters = useMemo(() => mapSearchFiltersToCatalogueParams(filters), [filters]);
	const { catalogues, total, fetchNextPage, hasNextPage, isFetchingNextPage, isPending, error, isError } =
		useInfiniteCatalogues({
			filters: queryFilters,
		});

	const apply = (next: CatalogueSearchFilters) => setSearchParams(serializeCatalogueListFilters(next));

	const handleFiltersChange = (next: CatalogueSearchFilters) => {
		setDraft(next);

		if (next.filterType !== draft.filterType || next.sortByWhat !== draft.sortByWhat) {
			apply(next);
		}
	};

	const keyword = filters.keyword.trim();

	const newCatalogueAction = isAuthenticated ? (
		<Button to={CATALOGUE_ROUTES.create} variant="primary">
			New catalogue
		</Button>
	) : undefined;

	if (isPending && catalogues.length === 0) {
		return (
			<LibraryPage>
				<PageHeader title="Catalogues" action={newCatalogueAction} />
				<PageLoading family="list" visual={<CataloguesListSkeleton />} />
			</LibraryPage>
		);
	}

	if (isError) {
		return (
			<LibraryPage>
				<PageHeader title="Catalogues" action={newCatalogueAction} />
				<Alert tone="danger">Error: {error.message}</Alert>
			</LibraryPage>
		);
	}

	const meta = [`Showing ${catalogues.length} of ${total}`, keyword !== '' && `Results for: ${keyword}`]
		.filter(Boolean)
		.join(' · ');
	const empty = emptyState(filters);

	return (
		<LibraryPage>
			<PageHeader title="Catalogues" meta={meta} action={newCatalogueAction} />

			<CatalogueFilters value={draft} onChange={handleFiltersChange} onSubmit={() => apply(draft)} />

			{catalogues.length === 0 ? (
				<LibraryEmptyState {...empty} />
			) : (
				<LibraryCardGrid>
					{catalogues.map((catalogue) => (
						<CatalogueCard key={catalogue.uuid} catalogue={catalogue} />
					))}
				</LibraryCardGrid>
			)}

			{/* The empty state already says there is nothing; "No more results" under it is noise. */}
			{catalogues.length > 0 && (
				<div className={styles.pager}>
					{isFetchingNextPage ? (
						<img src={Spinner} alt="Loading more..." className={styles.loadMoreSpinner} />
					) : hasNextPage ? (
						<Button variant="secondary-outline" className={styles.loadMore} onClick={() => fetchNextPage()}>
							Load More
						</Button>
					) : (
						<span className={styles.muted}>No more results</span>
					)}
				</div>
			)}
		</LibraryPage>
	);
};

export default CataloguesListPage;
