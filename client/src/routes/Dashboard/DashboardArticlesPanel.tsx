import React, { useCallback, useState } from 'react';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import { OwnerProcessingSubscription } from '@/api/articles/hooks/useOwnerProcessingSubscription';
import { usePendingArticles } from '@/api/articles/moderation';
import Spinner from '@/assets/images/spinner.gif';
import DashboardArticleItem from '@/components/features/dashboard/DashboardArticleItem';
import dashboardRowStyles from '@/components/features/dashboard/DashboardRow.module.css';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster, Stack } from '@/components/shared/layout';
import type { User } from '@/types';
import styles from './Dashboard.module.css';
import SearchBarDashboard from './SearchBarDashboard';
import type { SearchFilters } from './SearchBarDashboard';
import { DASHBOARD_TYPES, type DashboardType } from './dashboard.constants';

type DashboardArticleFilters = {
	search?: string;
};

interface DashboardArticlesPanelProps {
	dashboardView: DashboardType;
	isAuthenticated: boolean;
	currentUser: User | null;
	onToggleDashboardView: () => void;
}

const toDisplayCount = (value: number | string | undefined) => {
	const parsedValue = typeof value === 'number' ? value : Number(value);

	return Number.isFinite(parsedValue) ? parsedValue : 0;
};

const DashboardArticlesPanel: React.FC<DashboardArticlesPanelProps> = ({
	dashboardView,
	isAuthenticated,
	currentUser,
	onToggleDashboardView,
}) => {
	const [filters, setFilters] = useState<DashboardArticleFilters>({});
	const { articles, total, error, fetchNextPage, hasNextPage, isFetchingNextPage, status } = useInfiniteArticles({
		filters: {
			author_uid: currentUser?.uuid,
			// Canonical `q`, not the legacy `search` alias, so AFM-07 can retire it.
			// The backend rejects a one-character search, so do not send one.
			...(filters.search && filters.search.trim().length >= 2 ? { q: filters.search.trim() } : {}),
			include_stats_counts: true,
			// The dashboard renders no facet controls.
			include_facets: false,
		},
		enabled: dashboardView === DASHBOARD_TYPES.COMMON_USER && isAuthenticated && !!currentUser?.uuid,
	});

	const shouldFetchPendingArticles =
		dashboardView === DASHBOARD_TYPES.ADMIN && isAuthenticated && !!currentUser?.isAdmin;

	const pendingArticlesQuery = usePendingArticles({ enabled: shouldFetchPendingArticles });

	const handleFilterResults = useCallback((newFilters: SearchFilters) => {
		const keyword = newFilters.keyword.trim();
		setFilters(keyword ? { search: keyword } : {});
	}, []);

	const articleErrorMessage = error instanceof Error ? error.message : 'Failed to load articles.';
	const pendingArticlesErrorMessage =
		pendingArticlesQuery.error instanceof Error
			? pendingArticlesQuery.error.message
			: 'Failed to load pending articles.';
	const pendingArticles = shouldFetchPendingArticles ? pendingArticlesQuery.pendingArticles : [];

	return (
		<Stack gap="md">
			<div className={styles.toolbar}>
				<SearchBarDashboard searchType="articles" filterResults={handleFilterResults} />
			</div>

			<Stack as="section" gap="md" className={styles.panel}>
				{dashboardView === DASHBOARD_TYPES.ADMIN ? (
					<>
						<Cluster justify="between">
							<h4 className={styles.panelTitle}>Pending Articles - Admin view</h4>
							<Button variant="ghost" onClick={onToggleDashboardView}>
								User View <Icon name="chevron" rotate="270" />
							</Button>
						</Cluster>
						{shouldFetchPendingArticles && pendingArticlesQuery.isPending ? (
							<LoadingState altText="Loading pending articles..." />
						) : shouldFetchPendingArticles && pendingArticlesQuery.isError ? (
							<Alert tone="danger">{pendingArticlesErrorMessage}</Alert>
						) : pendingArticles.length ? (
							<>
								<ul className={styles.queue}>
									{pendingArticles.map((article) => (
										<li className={styles.queueRow} key={article.uuid}>
											<div>
												<h4 className={styles.queueTitle}>
													<Link to={`/articles/${article.uuid}`}>{article.title_jp}</Link>
												</h4>
												<Cluster gap="xs">
													<span className={styles.label}>tags:</span>
													<Cluster as="span" gap="3xs">
														{article.hashtags.map((tag) => (
															<Chip
																readonly
																key={tag.id + tag.content}
																title={tag.content}
																name={tag.content}
															>
																{tag.content}
															</Chip>
														))}
													</Cluster>
												</Cluster>
											</div>
											<small className={styles.meta}>
												{article.created_at}
												<br />
												duration from now(?) {article.created_at}
											</small>
											<div>
												<strong>{article.status_label}</strong>
											</div>
										</li>
									))}
								</ul>
								<LoadMore
									isFetchingNextPage={pendingArticlesQuery.isFetchingNextPage}
									hasNextPage={pendingArticlesQuery.hasNextPage}
									onLoadMore={() => pendingArticlesQuery.fetchNextPage()}
								/>
							</>
						) : (
							<Alert tone="info" className={styles.emptyState}>
								There are no articles to review.
							</Alert>
						)}
					</>
				) : (
					<>
						<Cluster justify="between">
							<h4 className={styles.panelTitle}>My Articles - User view</h4>
							<Button variant="ghost" onClick={onToggleDashboardView}>
								Admin View <Icon name="chevron" rotate="270" />
							</Button>
						</Cluster>
						<p className={styles.summary}>
							Showing {articles.length} of {total}
						</p>
						<div className={styles.columnHeadings}>
							<Cluster justify="between">
								<span>Title and Tags</span>
								<span>Status</span>
							</Cluster>
							<Cluster justify="between">
								<span>Stats</span>
								<span>Date and Action</span>
							</Cluster>
						</div>
						{status === 'pending' ? (
							<LoadingState altText="Loading articles..." />
						) : status === 'error' ? (
							<Alert tone="danger">{articleErrorMessage}</Alert>
						) : articles.length ? (
							<>
								{/* One channel for every article the owner lists (#263); polling covers the rest. */}
								{currentUser && <OwnerProcessingSubscription userUuid={currentUser.uuid} />}
								<ul className={dashboardRowStyles.list}>
									{articles.map((article) => (
										<DashboardArticleItem
											key={article.id}
											uuid={article.uuid}
											created_at={article.created_at}
											title_jp={article.title_jp}
											status={article.status}
											commentsTotal={toDisplayCount(article.engagement?.stats?.comments_count)}
											likesTotal={toDisplayCount(article.engagement?.stats?.likes_count)}
											viewsTotal={toDisplayCount(article.engagement?.stats?.views_count)}
											hashtags={article.hashtags}
										/>
									))}
								</ul>
								<LoadMore
									isFetchingNextPage={isFetchingNextPage}
									hasNextPage={hasNextPage}
									onLoadMore={() => fetchNextPage()}
								/>
							</>
						) : (
							<Alert tone="info" className={styles.emptyState}>
								You have no articles yet.
							</Alert>
						)}
					</>
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

const LoadMore: React.FC<{ isFetchingNextPage: boolean; hasNextPage: boolean; onLoadMore: () => void }> = ({
	isFetchingNextPage,
	hasNextPage,
	onLoadMore,
}) => (
	<Cluster justify="center" className={styles.loadMore}>
		{isFetchingNextPage ? (
			<img src={Spinner} alt="Loading more..." className={styles.loadMoreSpinner} />
		) : hasNextPage ? (
			<Button variant="secondary-outline" className={styles.loadMoreButton} onClick={onLoadMore}>
				Load More
			</Button>
		) : (
			<span className={styles.label}>No more results</span>
		)}
	</Cluster>
);

export default DashboardArticlesPanel;
