import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import type { InfiniteData, QueryClient, UseMutationOptions } from '@tanstack/react-query';
import { articlePending, articleSetStatus, getArticlePendingQueryKey } from '@/api/generated/article/article';
import type { ArticlePendingQueryError } from '@/api/generated/article/article';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleModerationListResource } from '@/api/generated/model/articleModerationListResource';
import type { ArticlePendingParams } from '@/api/generated/model/articlePendingParams';
import { ArticleStatus } from '@/api/generated/model/articleStatus';
import type { ArticleStatusResource } from '@/api/generated/model/articleStatusResource';
import { getArticleDetailQueryKey } from './details';

/**
 * Named vocabulary for the generated `ArticleStatus` enum, which orval emits as
 * `NUMBER_0 .. NUMBER_4`. `satisfies` keeps the values pinned to the contract: if the
 * backend enum loses a member, this stops compiling.
 *
 * Mirrors `App\Domain\Shared\Enums\ArticleStatus`.
 */
export const ARTICLE_STATUS = {
	PENDING: ArticleStatus.NUMBER_0,
	PROCESSED: ArticleStatus.NUMBER_1,
	REVIEWING: ArticleStatus.NUMBER_2,
	REJECTED: ArticleStatus.NUMBER_3,
	APPROVED: ArticleStatus.NUMBER_4,
} as const satisfies Record<string, ArticleStatus>;

/**
 * The statuses `ArticleRepository::findModerationQueue` selects on. An article whose
 * status moves outside this set has left the review queue.
 */
export const MODERATION_QUEUE_STATUSES: readonly number[] = [ARTICLE_STATUS.PENDING, ARTICLE_STATUS.REVIEWING];

/**
 * The four choices an admin can apply from the review modal, in the order they have
 * always been offered. The labels are unchanged; only the wire values now follow the
 * v1 enum instead of the retired legacy numbering.
 */
export const ARTICLE_MODERATION_CHOICES = [
	{ value: ARTICLE_STATUS.PENDING, label: 'Pending' },
	{ value: ARTICLE_STATUS.REVIEWING, label: 'Review' },
	{ value: ARTICLE_STATUS.REJECTED, label: 'Reject' },
	{ value: ARTICLE_STATUS.APPROVED, label: 'Approve' },
] as const satisfies ReadonlyArray<{ value: ArticleStatus; label: string }>;

export const isModerationQueueStatus = (status: number) => MODERATION_QUEUE_STATUSES.includes(status);

export type PendingArticlesFilters = Omit<ArticlePendingParams, 'page'>;

export const getPendingArticlesQueryKey = (filters: PendingArticlesFilters = {}) => getArticlePendingQueryKey(filters);

export const getNextPendingArticlesPageParam = (lastPage: ArticleModerationListResource) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getPendingArticlesTotal = (pages: ArticleModerationListResource[] | undefined) =>
	pages?.[0]?.pagination.total ?? 0;

type UsePendingArticlesOptions = {
	enabled?: boolean;
	filters?: PendingArticlesFilters;
};

export const usePendingArticles = ({ enabled = true, filters = {} }: UsePendingArticlesOptions = {}) => {
	const query = useInfiniteQuery<
		ArticleModerationListResource,
		ArticlePendingQueryError,
		InfiniteData<ArticleModerationListResource>,
		ReturnType<typeof getPendingArticlesQueryKey>,
		number
	>({
		queryKey: getPendingArticlesQueryKey(filters),
		queryFn: ({ pageParam, signal }) => articlePending({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextPendingArticlesPageParam,
		enabled,
	});

	const pages = query.data?.pages as ArticleModerationListResource[] | undefined;

	return {
		...query,
		pendingArticles: pages?.flatMap((page) => page.items) ?? [],
		total: getPendingArticlesTotal(pages),
	};
};

/**
 * Writes the *persisted* status onto the cached detail resource. The detail contract
 * carries no `status_label`, so only the numeric status is reconciled here.
 */
export const applyStatusToArticleDetail = (
	detail: ArticleDetailResource | undefined,
	persisted: ArticleStatusResource,
): ArticleDetailResource | undefined => (detail ? { ...detail, status: persisted.status } : detail);

/**
 * Reconciles the pending pages against the persisted status: the row is updated in
 * place while the article is still reviewable, and dropped once it leaves the queue.
 * Pagination totals are left alone - the caller invalidates so the server resettles
 * them rather than us guessing.
 */
export const applyStatusToPendingArticles = (
	data: InfiniteData<ArticleModerationListResource> | undefined,
	persisted: ArticleStatusResource,
): InfiniteData<ArticleModerationListResource> | undefined => {
	if (!data) return data;

	const staysInQueue = isModerationQueueStatus(persisted.status);

	return {
		...data,
		pages: data.pages.map((page) => ({
			...page,
			items: staysInQueue
				? page.items.map((item) =>
						item.uuid === persisted.uuid
							? { ...item, status: persisted.status, status_label: persisted.status_label }
							: item,
					)
				: page.items.filter((item) => item.uuid !== persisted.uuid),
		})),
	};
};

type ArticleStatusMutationCallbacks = {
	onSuccess?: (persisted: ArticleStatusResource) => void;
	onError?: (error: unknown) => void;
};

/**
 * Split out from the hook so the reconciliation contract can be exercised without a
 * React tree: build the options against a stub client and drive them directly.
 */
export const createArticleStatusMutationOptions = (
	articleUuid: string,
	queryClient: Pick<QueryClient, 'setQueryData' | 'invalidateQueries'>,
	{ onSuccess, onError }: ArticleStatusMutationCallbacks = {},
): UseMutationOptions<ArticleStatusResource, unknown, ArticleStatus> => ({
	mutationFn: (status) => articleSetStatus(articleUuid, { status }),

	onSuccess: (persisted) => {
		queryClient.setQueryData<ArticleDetailResource>(getArticleDetailQueryKey(articleUuid), (detail) =>
			applyStatusToArticleDetail(detail, persisted),
		);
		queryClient.setQueryData<InfiniteData<ArticleModerationListResource>>(getPendingArticlesQueryKey(), (data) =>
			applyStatusToPendingArticles(data, persisted),
		);
		queryClient.invalidateQueries({ queryKey: getPendingArticlesQueryKey() });

		onSuccess?.(persisted);
	},

	// Deliberately no cache write: a failed moderation leaves both the detail and the
	// queue showing whatever the server last confirmed.
	onError: (error) => {
		console.error('Article status update failed', error);
		onError?.(error);
	},
});

export const useArticleStatusMutation = (articleUuid: string, callbacks: ArticleStatusMutationCallbacks = {}) => {
	const queryClient = useQueryClient();

	return useMutation(createArticleStatusMutationOptions(articleUuid, queryClient, callbacks));
};
