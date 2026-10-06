import React, { useCallback, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useDeleteCatalogueMutation } from '@/api/catalogues/hooks/useDeleteCatalogueMutation';
import { useInfiniteCatalogues } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import type { CatalogueResource } from '@/api/generated/model';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { CatalogueFilters, type CatalogueSearchFilters } from '@/components/features/catalogues/CatalogueFilters';
import { DashboardListsTable } from '@/components/features/dashboard/DashboardListsTable';
import { Alert } from '@/components/shared/Alert';
import type { DataTableEmpty } from '@/components/shared/DataTable';
import { useModal } from '@/hooks/useModal';
import { CATALOGUE_ROUTES, CATALOGUE_TYPE_FILTER_ALL, isCustomCatalogueType } from '@/shared/constants/catalogues';
import type { User } from '@/types';
import { DASHBOARD_PER_PAGE, DashboardListSection } from './DashboardListSection';
import { LIST_SORTS, type DashboardViewChange, type DashboardViewState, type ListSort } from './dashboardSearchParams';
import { useDashboardKeyword } from './useDashboardKeyword';

type DashboardCatalogueFilters = {
	search?: string;
	sort_by: 'created_at' | 'views';
	sort_dir: 'desc';
	type?: number;
};

interface DashboardCataloguesPanelProps {
	user: User;
	view: DashboardViewState;
	onViewChange: DashboardViewChange;
}

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

/** The request for one view of the owner's lists, built-in Known lists included. */
export const dashboardCatalogueFilters = (
	ownerUuid: string,
	{ q, listType, listSort }: Pick<DashboardViewState, 'q' | 'listType' | 'listSort'>,
): FetchCataloguesFilters => {
	const filters = mapDashboardSearchFiltersToCatalogueFilters({
		keyword: q,
		sortByWhat: listSort,
		filterType: listType,
	});

	return {
		owner_uid: ownerUuid,
		search: filters.search,
		sort_by: filters.sort_by,
		sort_dir: filters.sort_dir,
		type: filters.type,
		public_only: false,
		custom_only: false,
		per_page: DASHBOARD_PER_PAGE,
		include_stats_counts: true,
		// The table shows no tags.
		include_hashtags: false,
	};
};

const toListSort = (value: string): ListSort =>
	(LIST_SORTS as readonly string[]).includes(value) ? (value as ListSort) : 'new';

const emptyLists = (view: DashboardViewState): DataTableEmpty => {
	const search = view.q.trim();

	if (search !== '') {
		return { title: `No lists match “${search}”`, hint: 'Try a shorter search, or set the type back to All.' };
	}

	if (view.listType !== CATALOGUE_TYPE_FILTER_ALL) {
		return { title: 'No lists of this type', hint: 'Set the type back to All to see every list.' };
	}

	return {
		title: 'You have no lists yet',
		hint: (
			<>
				<Link to={CATALOGUE_ROUTES.create}>Create a list</Link> to collect kanji, words, sentences or articles.
			</>
		),
	};
};

/** The Lists tab: the signed-in user's lists, with search, a type filter and a sort. */
const DashboardCataloguesPanel: React.FC<DashboardCataloguesPanelProps> = ({ user, view, onViewChange }) => {
	const commitKeyword = useCallback((q: string) => onViewChange({ q }, { replace: true }), [onViewChange]);
	const { draft, setDraft, applyNow } = useDashboardKeyword(view.q, commitKeyword);

	const { q, listType, listSort } = view;
	const filters = useMemo(
		() => dashboardCatalogueFilters(user.uuid, { q, listType, listSort }),
		[user.uuid, q, listType, listSort],
	);
	const { catalogues, ...query } = useInfiniteCatalogues({ filters });

	const summaryRef = useRef<HTMLParagraphElement>(null);
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(dialogRef, { id: 'dashboard-list-delete' });
	const [target, setTarget] = useState<CatalogueResource | null>(null);
	const [deleteFailed, setDeleteFailed] = useState(false);
	const deleteMutation = useDeleteCatalogueMutation();
	const { open: openDeleteModal, close: closeDeleteModal } = deleteModal;

	const handleDelete = useCallback(
		(catalogue: CatalogueResource) => {
			setDeleteFailed(false);
			setTarget(catalogue);
			openDeleteModal();
		},
		[openDeleteModal],
	);

	const confirmDelete = () => {
		if (!target) return;

		deleteMutation.mutate(target.uuid, {
			onSuccess: () => {
				closeDeleteModal();
				summaryRef.current?.focus();
			},
			onError: () => {
				closeDeleteModal();
				setDeleteFailed(true);
			},
		});
	};

	// Keyword edits stay a draft until the pause; type and sort apply at once.
	const handleFiltersChange = (next: CatalogueSearchFilters) => {
		if (next.keyword !== draft) {
			setDraft(next.keyword);
		}

		if (next.filterType !== view.listType || next.sortByWhat !== view.listSort) {
			onViewChange({ listType: next.filterType, listSort: toListSort(next.sortByWhat) });
		}
	};

	return (
		<>
			<DashboardListSection
				noun="lists"
				subject="Your lists"
				itemCount={catalogues.length}
				query={query}
				summaryRef={summaryRef}
				notice={
					deleteFailed ? (
						<Alert tone="danger">The list could not be deleted. Try again in a moment.</Alert>
					) : null
				}
				filters={
					<CatalogueFilters
						value={{ keyword: draft, filterType: view.listType, sortByWhat: view.listSort }}
						onChange={handleFiltersChange}
						onSubmit={applyNow}
					/>
				}
			>
				{({ loading }) => (
					<DashboardListsTable
						catalogues={catalogues}
						loading={loading}
						empty={emptyLists(view)}
						onDelete={handleDelete}
					/>
				)}
			</DashboardListSection>
			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={target?.title ?? ''}
				onDelete={confirmDelete}
				isProcessing={deleteMutation.isPending}
				title="Delete this list?"
				deleteLabel="Delete list"
				ariaLabel="Delete list"
			/>
		</>
	);
};

export default DashboardCataloguesPanel;
