import React, { useCallback, useDeferredValue, useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import type { ArticleHashtagResource } from '@/api/generated/model/articleHashtagResource';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import Spinner from '@/assets/images/spinner.gif';
import DashboardArticleItem from '@/components/features/dashboard/DashboardArticleItem';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import type { User } from '@/types';
import SearchBarDashboard from './SearchBarDashboard';
import type { SearchFilters } from './SearchBarDashboard';
import { DASHBOARD_TYPES, type DashboardType } from './dashboard.constants';

type DashboardArticleFilters = {
	search?: string;
};

type DashboardArticleHashtag = Pick<ArticleHashtagResource, 'id' | 'content'>;

interface DashboardArticlesPanelProps {
	dashboardView: DashboardType;
	isAuthenticated: boolean;
	currentUser: User | null;
	onToggleDashboardView: () => void;
}

interface PendingArticle {
	id: number;
	uuid?: string;
	title_jp: string;
	hashtags?: DashboardArticleHashtag[];
	created_at: string;
	statusTitle?: string;
}

interface PendingArticlesResponse {
	articlesPending: PendingArticle[];
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
			search: filters.search,
			include_stats_counts: true,
		},
		enabled: dashboardView === DASHBOARD_TYPES.COMMON_USER && isAuthenticated && !!currentUser?.uuid,
	});

	const shouldFetchPendingArticles =
		dashboardView === DASHBOARD_TYPES.ADMIN && isAuthenticated && !!currentUser?.isAdmin;

	const pendingArticlesQuery = useQuery({
		queryKey: ['dashboard', 'pending-articles', currentUser?.uuid ?? null],
		queryFn: () => apiCall<PendingArticlesResponse>({ method: HttpMethod.GET, path: '/articles/pendinglist' }),
		enabled: shouldFetchPendingArticles,
	});

	const trackedArticleUuids = useMemo(() => {
		return articles
			.filter(
				(article) =>
					article.processing_status?.status !== undefined &&
					article.processing_status?.status !== LastOperationStatus.Completed,
			)
			.map((article) => article.uuid);
	}, [articles]);
	const deferredTrackedUuids = useDeferredValue(trackedArticleUuids);

	const handleFilterResults = useCallback((newFilters: SearchFilters) => {
		const keyword = newFilters.keyword.trim();
		setFilters(keyword ? { search: keyword } : {});
	}, []);

	const articleErrorMessage = error instanceof Error ? error.message : 'Failed to load articles.';
	const pendingArticlesErrorMessage =
		pendingArticlesQuery.error instanceof Error
			? pendingArticlesQuery.error.message
			: 'Failed to load pending articles.';
	const pendingArticles = shouldFetchPendingArticles ? pendingArticlesQuery.data?.articlesPending ?? [] : [];

	return (
		<>
			<div className="ml-3 mt-2">
				<SearchBarDashboard searchType="articles" filterResults={handleFilterResults} />
			</div>

			<div className="my-3 p-3 bg-white rounded box-shadow">
				{dashboardView === DASHBOARD_TYPES.ADMIN ? (
					<>
						<div className="d-flex justify-content-between align-items-center mb-3">
							<h4>Pending Articles - Admin view</h4>
							<Button variant="ghost" onClick={onToggleDashboardView}>
								User View <Icon name="chevron" rotate="270" />
							</Button>
						</div>
						<div className="col-lg-12 col-md-12 mx-auto">
							{shouldFetchPendingArticles && pendingArticlesQuery.isPending ? (
								<LoadingState altText="Loading pending articles..." />
							) : shouldFetchPendingArticles && pendingArticlesQuery.isError ? (
								<div className="alert alert-danger">{pendingArticlesErrorMessage}</div>
							) : pendingArticles.length ? (
								pendingArticles.map((article) => (
									<div className="row pb-3 mb-0 mt-3 border-bottom border-gray" key={article.id}>
										<div className="col-lg-6">
											<h4>
												<Link
													to={article.uuid ? `/articles/${article.uuid}` : `/article/${article.id}`}
												>
													{article.title_jp}
												</Link>
											</h4>
											tags:{' '}
											<section className="mt-2 d-flex align-items-center flex-wrap">
												{(article.hashtags ?? []).map((tag) => (
													<Chip
														className="mr-1"
														readonly
														key={tag.id + tag.content}
														title={tag.content}
														name={tag.content}
													>
														{tag.content}
													</Chip>
												))}
											</section>
										</div>
										<div className="col-lg-4 col-12-sm pt-3">
											<small className="text-muted">
												{article.created_at}
												<br />
												duration from now(?) {article.created_at}
											</small>
										</div>
										<div className="col-lg-2">
											<strong>{article.statusTitle}</strong>
										</div>
									</div>
								))
							) : (
								<div className="alert text-center alert-info">There are no articles to review.</div>
							)}
						</div>
					</>
				) : (
					<>
						<div className="d-flex justify-content-between align-items-center mb-3">
							<h4>My Articles - User view</h4>
							<Button variant="ghost" onClick={onToggleDashboardView}>
								Admin View <Icon name="chevron" rotate="270" />
							</Button>
						</div>
						<div className="col-lg-12 col-md-10 mx-auto">
							<div className="mb-3 text-muted">
								Showing {articles.length} of {total}
							</div>
							<div className="row d-none d-md-flex pb-2 mb-3 border-bottom border-gray small text-uppercase text-muted">
								<div className="col-md-8 d-flex justify-content-between">
									<span>Title and Tags</span>
									<span>Status</span>
								</div>
								<div className="col-md-4 d-flex justify-content-between">
									<span>Stats</span>
									<span>Date and Action</span>
								</div>
							</div>
							{status === 'pending' ? (
								<LoadingState altText="Loading articles..." />
							) : status === 'error' ? (
								<div className="alert alert-danger">{articleErrorMessage}</div>
							) : articles.length ? (
								<>
									{deferredTrackedUuids.map((uuid) => (
										<ArticleSubscription key={uuid} uuid={uuid} />
									))}
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
								<div className="alert text-center alert-info">You have no articles yet.</div>
							)}
						</div>
					</>
				)}
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

const ArticleSubscription: React.FC<{ uuid: string }> = ({ uuid }) => {
	useArticleSubscription(uuid);
	return null;
};

export default DashboardArticlesPanel;
