import React, { useCallback, useMemo, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useDeleteArticleMutation } from '@/api/articles/hooks/useDeleteArticleMutation';
import { useInfiniteArticles, type ArticleListFilters } from '@/api/articles/hooks/useInfiniteArticles';
import { OwnerProcessingSubscription } from '@/api/articles/hooks/useOwnerProcessingSubscription';
import type { ArticleResource } from '@/api/generated/model';
import { DeleteInstanceModal } from '@/components/features/DeleteInstanceModal';
import { DashboardArticlesTable } from '@/components/features/dashboard/DashboardArticlesTable';
import { Alert } from '@/components/shared/Alert';
import type { DataTableEmpty } from '@/components/shared/DataTable';
import { FilterBar } from '@/components/shared/FilterBar';
import { useModal } from '@/hooks/useModal';
import type { User } from '@/types';
import { DASHBOARD_PER_PAGE, DashboardListSection } from './DashboardListSection';
import {
	ARTICLE_STATUS_FILTER_OPTIONS,
	statusesForFilter,
	type ArticleStatusFilter,
	type DashboardViewChange,
	type DashboardViewState,
} from './dashboardSearchParams';
import { useDashboardKeyword } from './useDashboardKeyword';

/** The backend rejects a shorter search (SearchTerm::MIN_LENGTH), so it is not sent. */
const MIN_SEARCH_LENGTH = 2;

interface DashboardArticlesPanelProps {
	user: User;
	view: DashboardViewState;
	onViewChange: DashboardViewChange;
}

/** The request for one view of the owner's articles. Exported so the mapping can be tested alone. */
export const dashboardArticleFilters = (
	ownerUuid: string,
	q: string,
	status: ArticleStatusFilter,
): ArticleListFilters => {
	const search = q.trim();
	const statuses = statusesForFilter(status);

	return {
		author_uid: ownerUuid,
		// Canonical `q`, not the legacy `search` alias.
		...(search.length >= MIN_SEARCH_LENGTH ? { q: search } : {}),
		...(statuses.length > 0 ? { 'statuses[]': statuses } : {}),
		per_page: DASHBOARD_PER_PAGE,
		include_stats_counts: true,
		// The table shows no tags and no facet controls.
		include_hashtags: false,
		include_facets: false,
	};
};

const emptyArticles = (q: string, status: ArticleStatusFilter): DataTableEmpty => {
	const search = q.trim();

	if (search !== '') {
		return {
			title: (
				<>
					No articles match “<span lang="ja">{search}</span>”
				</>
			),
			hint: 'Try a shorter search, or set Approval back to All.',
		};
	}

	if (status !== 'all') {
		return { title: 'No articles match this filter', hint: 'Set Approval back to All to see every article.' };
	}

	return {
		title: 'You have no articles yet',
		hint: (
			<>
				<Link to="/newarticle">Write your first article</Link>. It is analysed for kanji and words, and a
				reviewer may check it before it is public.
			</>
		),
	};
};

/** The Articles tab: the signed-in user's own articles, with search and an approval filter. */
const DashboardArticlesPanel: React.FC<DashboardArticlesPanelProps> = ({ user, view, onViewChange }) => {
	const commitKeyword = useCallback((q: string) => onViewChange({ q }, { replace: true }), [onViewChange]);
	const { draft, setDraft, applyNow } = useDashboardKeyword(view.q, commitKeyword);

	const filters = useMemo(
		() => dashboardArticleFilters(user.uuid, view.q, view.status),
		[user.uuid, view.q, view.status],
	);
	const { articles, ...query } = useInfiniteArticles({ filters });

	const summaryRef = useRef<HTMLParagraphElement>(null);
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(dialogRef, { id: 'dashboard-article-delete' });
	const [target, setTarget] = useState<ArticleResource | null>(null);
	const [deleteFailed, setDeleteFailed] = useState(false);
	const deleteMutation = useDeleteArticleMutation();
	const { open: openDeleteModal, close: closeDeleteModal } = deleteModal;

	const handleDelete = useCallback(
		(article: ArticleResource) => {
			setDeleteFailed(false);
			setTarget(article);
			openDeleteModal();
		},
		[openDeleteModal],
	);

	const confirmDelete = () => {
		if (!target) return;

		deleteMutation.mutate(target.uuid, {
			onSuccess: () => {
				closeDeleteModal();
				// The row, and the button that had focus, are gone: land on the count line.
				summaryRef.current?.focus();
			},
			onError: () => {
				closeDeleteModal();
				setDeleteFailed(true);
			},
		});
	};

	return (
		<>
			{/* One channel for every article the owner lists (#263); polling covers the rest. */}
			<OwnerProcessingSubscription userUuid={user.uuid} />
			<DashboardListSection
				noun="articles"
				subject="Your articles"
				itemCount={articles.length}
				query={query}
				summaryRef={summaryRef}
				notice={
					deleteFailed ? (
						<Alert tone="danger">The article could not be deleted. Try again in a moment.</Alert>
					) : null
				}
				filters={
					<FilterBar onSubmit={applyNow} label="Article filters">
						<FilterBar.Search
							label="Search your articles"
							placeholder="Ex.: title, text, #tag"
							value={draft}
							onChange={setDraft}
						/>
						<FilterBar.Filters>
							<FilterBar.Select
								label="Approval"
								value={view.status}
								options={ARTICLE_STATUS_FILTER_OPTIONS}
								onChange={(status) => onViewChange({ status })}
							/>
						</FilterBar.Filters>
					</FilterBar>
				}
			>
				{({ loading }) => (
					<DashboardArticlesTable
						articles={articles}
						loading={loading}
						empty={emptyArticles(view.q, view.status)}
						onDelete={handleDelete}
					/>
				)}
			</DashboardListSection>
			<DeleteInstanceModal
				controller={deleteModal}
				instanceName={target?.title_jp ?? ''}
				onDelete={confirmDelete}
				isProcessing={deleteMutation.isPending}
				title="Delete this article?"
				deleteLabel="Delete article"
				ariaLabel="Delete article"
			/>
		</>
	);
};

export default DashboardArticlesPanel;
