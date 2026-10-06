import { useQuery } from '@tanstack/react-query';
import { articleIndex } from '@/api/generated/article/article';
import { catalogueIndex } from '@/api/generated/catalogue/catalogue';
import type { ArticleStatus } from '@/api/generated/model/articleStatus';
import { dashboardKeys } from './keys';

export interface DashboardCounts {
	articles: number;
	lists: number;
	awaitingReview: number;
}

/** One row per request, with every include off: only `pagination.total` is read. */
const COUNT_ONLY = { per_page: 1, include_stats_counts: false, include_hashtags: false } as const;

/**
 * The owner's totals for the dashboard header, unfiltered so they do not move while the tables
 * are filtered (UI-DASH-00, #450). Three one-row reads of the existing list endpoints, run in
 * parallel under one key; no summary endpoint.
 */
export const fetchDashboardCounts = async (
	ownerUuid: string,
	awaitingStatuses: readonly ArticleStatus[],
	signal?: AbortSignal,
): Promise<DashboardCounts> => {
	const [articles, lists, awaiting] = await Promise.all([
		articleIndex({ author_uid: ownerUuid, ...COUNT_ONLY, include_facets: false }, undefined, signal),
		catalogueIndex(
			{ owner_uid: ownerUuid, ...COUNT_ONLY, public_only: false, custom_only: false },
			undefined,
			signal,
		),
		articleIndex(
			{ author_uid: ownerUuid, ...COUNT_ONLY, include_facets: false, 'statuses[]': [...awaitingStatuses] },
			undefined,
			signal,
		),
	]);

	return {
		articles: articles.pagination.total,
		lists: lists.pagination.total,
		awaitingReview: awaiting.pagination.total,
	};
};

export const useDashboardCounts = (ownerUuid: string | undefined, awaitingStatuses: readonly ArticleStatus[]) =>
	useQuery({
		queryKey: dashboardKeys.counts(ownerUuid ?? ''),
		queryFn: ({ signal }) => fetchDashboardCounts(ownerUuid as string, awaitingStatuses, signal),
		enabled: Boolean(ownerUuid),
	});
