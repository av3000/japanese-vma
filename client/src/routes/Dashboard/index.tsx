import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { Article, fetchArticles, Hashtag } from '@/api/articles/articles';
import { useArticleSubscription } from '@/api/articles/hooks/useArticleSubscription';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import Spinner from '@/assets/images/spinner.gif';
import DashboardArticleItem from '@/components/features/dashboard/DashboardArticleItem';
import DashboardListItem from '@/components/features/dashboard/DashboardListItem';
import { Button } from '@/components/shared/Button';
import { Chip } from '@/components/shared/Chip';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import SearchBarDashboard, { SearchFilters } from './SearchBarDashboard';

const RESOURCE_TYPES = {
	ARTICLES: 'ARTICLES',
	LISTS: 'LISTS',
};

const DASHBOARD_TYPES = {
	ADMIN: 'ADMIN',
	COMMON_USER: 'COMMON_USER',
};

interface DashboardListSummary {
	id: number;
	created_at: string;
	title: string;
	commentsTotal: number;
	likesTotal: number;
	viewsTotal: number;
	hashtags: Hashtag[];
	typeTitle: string;
}

interface PendingArticle {
	id: number;
	uuid?: string;
	title_jp: string;
	hashtags?: Hashtag[];
	created_at: string;
	statusTitle?: string;
}

const DashboardList: React.FC = () => {
	const [currentResource, setCurrentResource] = useState(RESOURCE_TYPES.LISTS);
	const [lists, setLists] = useState<DashboardListSummary[]>([]);
	const [articlesPending, setArticlesPending] = useState<PendingArticle[]>([]);
	const [dashboard, setDashboard] = useState(DASHBOARD_TYPES.COMMON_USER);
	const [articleFilters, setArticleFilters] = useState<{ search?: string }>({});
	const [isListsLoading, setIsListsLoading] = useState(true);
	const [isPendingArticlesLoading, setIsPendingArticlesLoading] = useState(false);

	const { isAuthenticated, user: currentUser } = useAuth();

	useEffect(() => {
		if (isAuthenticated) {
			fetchLists();
		}
	}, [currentUser, isAuthenticated]);

	const { data, error, fetchNextPage, hasNextPage, isFetchingNextPage, status } = useInfiniteQuery({
		queryKey: [
			'articles',
			{
				scope: 'dashboard',
				author_uid: currentUser?.uuid ?? null,
				search: articleFilters.search ?? null,
			},
		],
		queryFn: ({ pageParam }) => {
			if (!currentUser?.uuid) {
				throw new Error('Missing current user UUID');
			}

			return fetchArticles(
				{
					author_uid: currentUser.uuid,
					search: articleFilters.search,
					include_stats_counts: true,
				},
				pageParam,
			);
		},
		initialPageParam: 1,
		getNextPageParam: (lastPage) => {
			return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
		},
		enabled:
			isAuthenticated &&
			!!currentUser?.uuid &&
			currentResource === RESOURCE_TYPES.ARTICLES &&
			dashboard === DASHBOARD_TYPES.COMMON_USER,
	});

	const allArticles = useMemo<Article[]>(() => data?.pages.flatMap((page) => page.items) ?? [], [data?.pages]);
	const totalArticleCount = data?.pages[0]?.pagination.total ?? 0;

	const trackedArticleUuids = useMemo(() => {
		return allArticles
			.filter(
				(article) =>
					article.processing_status?.status !== undefined &&
					article.processing_status?.status !== LastOperationStatus.Completed,
			)
			.map((article) => article.uuid);
	}, [allArticles]);

	const [debouncedTrackedUuids, setDebouncedTrackedUuids] = useState<string[]>([]);

	useEffect(() => {
		const timeout = window.setTimeout(() => {
			setDebouncedTrackedUuids(trackedArticleUuids);
		}, 300);

		return () => window.clearTimeout(timeout);
	}, [trackedArticleUuids]);

	const toggleResource = () => {
		setCurrentResource((prev) => (prev === RESOURCE_TYPES.LISTS ? RESOURCE_TYPES.ARTICLES : RESOURCE_TYPES.LISTS));
	};

	const toggleDashboard = () => {
		const nextDashboard =
			dashboard === DASHBOARD_TYPES.COMMON_USER ? DASHBOARD_TYPES.ADMIN : DASHBOARD_TYPES.COMMON_USER;

		setDashboard(nextDashboard);

		if (nextDashboard === DASHBOARD_TYPES.ADMIN && currentUser?.isAdmin) {
			fetchArticlesPending();
		}
	};

	const fetchArticlesPending = async () => {
		try {
			setIsPendingArticlesLoading(true);
			const res = await apiCall({ method: HttpMethod.GET, path: '/articles/pendinglist' });
			setArticlesPending(res.articlesPending);
		} catch (err) {
			console.error(err);
		} finally {
			setIsPendingArticlesLoading(false);
		}
	};

	const fetchLists = async () => {
		try {
			setIsListsLoading(true);
			const res = await apiCall({ method: HttpMethod.GET, path: '/user/lists' });
			setLists(res.lists);
		} catch (err) {
			console.error(err);
		} finally {
			setIsListsLoading(false);
		}
	};

	const handleFilterResults = useCallback(
		(newFilters: SearchFilters) => {
			if (currentResource !== RESOURCE_TYPES.ARTICLES) {
				return;
			}

			const keyword = newFilters.keyword.trim();
			setArticleFilters(keyword ? { search: keyword } : {});
		},
		[currentResource],
	);

	const loadingSpinner = () => (
		<div className="container mt-5">
			<div className="row justify-content-center">
				<img src={Spinner} alt="Loading..." />
			</div>
		</div>
	);

	const articleErrorMessage = error instanceof Error ? error.message : 'Failed to load articles.';

	const mainContent =
		currentResource === RESOURCE_TYPES.LISTS ? (
			isListsLoading ? (
				loadingSpinner()
			) : (
				<div className="my-3 p-3 bg-white rounded box-shadow">
					<div className="d-flex justify-content-between align-items-center mb-3">
						<h4 className="border-bottom border-gray pb-2 mb-0">My Lists</h4>
					</div>
					<div className="col-lg-12 col-md-10 mx-auto">
						{lists.length > 0 ? (
							lists.map((list) => <DashboardListItem key={list.id} {...list} />)
						) : (
							<div className="alert text-center alert-info">You have no Lists yet.</div>
						)}
					</div>
				</div>
			)
		) : (
			<div className="my-3 p-3 bg-white rounded box-shadow">
				{dashboard === DASHBOARD_TYPES.ADMIN ? (
					<>
						<div className="d-flex justify-content-between align-items-center mb-3">
							<h4>Pending Articles - Admin view</h4>
							<Button variant="ghost" onClick={toggleDashboard}>
								User View <Icon name="chevron" rotate="270" />
							</Button>
						</div>
						<div className="col-lg-12 col-md-12 mx-auto">
							{isPendingArticlesLoading ? (
								loadingSpinner()
							) : articlesPending.length ? (
								articlesPending.map((article) => (
									<div className="row pb-3 mb-0 mt-3 border-bottom border-gray" key={article.id}>
										<div className="col-lg-6">
											<h4>
												<Link
													to={
														article.uuid
															? `/articles/${article.uuid}`
															: `/article/${article.id}`
													}
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
							<Button variant="ghost" onClick={toggleDashboard}>
								Admin View <Icon name="chevron" rotate="270" />
							</Button>
						</div>
						<div className="col-lg-12 col-md-10 mx-auto">
							<div className="mb-3 text-muted">
								Showing {allArticles.length} of {totalArticleCount}
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
								loadingSpinner()
							) : status === 'error' ? (
								<div className="alert alert-danger">{articleErrorMessage}</div>
							) : allArticles.length ? (
								<>
									{debouncedTrackedUuids.map((uuid) => (
										<ArticleSubscription key={uuid} uuid={uuid} />
									))}
									{allArticles.map((article) => (
										<DashboardArticleItem
											key={article.id}
											uuid={article.uuid}
											created_at={article.created_at}
											title_jp={article.title_jp}
											status={article.status}
											commentsTotal={article.engagement?.stats?.comments_count ?? 0}
											likesTotal={article.engagement?.stats?.likes_count ?? 0}
											viewsTotal={article.engagement?.stats?.views_count ?? 0}
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
		);

	return (
		<div className="container mt-5">
			<div className="container mt-5">
				<div className="ml-3 mt-2">
					<div className="row align-items-center">
						<div className="col-auto">
							<Button variant="ghost" onClick={toggleResource}>
								{currentResource === RESOURCE_TYPES.LISTS ? 'Articles' : 'Lists'}{' '}
								<Icon name="chevron" rotate="270" />
							</Button>
						</div>

						<div className="col">
							<SearchBarDashboard
								searchType={currentResource === RESOURCE_TYPES.LISTS ? 'lists' : 'articles'}
								filterResults={handleFilterResults}
							/>
						</div>
					</div>
				</div>
				{mainContent}
			</div>
		</div>
	);
};

const ArticleSubscription: React.FC<{ uuid: string }> = ({ uuid }) => {
	useArticleSubscription(uuid);
	return null;
};

export default DashboardList;
