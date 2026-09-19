import type { InfiniteData, QueryClient } from '@tanstack/react-query';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { articleKeys } from './keys';

/**
 * Both the socket and the polling fallback land processing status in the same two places:
 * the article detail entry and every cached list page that contains the article. This
 * module is that single write path, kept free of React so it can be exercised against a
 * real `QueryClient` in tests.
 */

/**
 * `superseded` is terminal too (ADR 0001): the run's result was discarded because the content
 * moved on, and a newer run owns the row from then on.
 */
/**
 * The one task type the backend reports for an article since ADR 0001. Events carrying any
 * other type are ignored so a stray legacy payload cannot corrupt the cache.
 */
export const ARTICLE_CONTENT_PROCESSING_TASK = 'article_content_processing';

export const isArticleContentProcessingPayload = (payload: ProcessingStatusResource): boolean =>
	payload.type === ARTICLE_CONTENT_PROCESSING_TASK;

/** Echo may hand the event over as a JSON string depending on the broadcaster. */
export const normalizeProcessingPayload = (payload: ProcessingStatusResource | string): ProcessingStatusResource =>
	typeof payload === 'string' ? (JSON.parse(payload) as ProcessingStatusResource) : payload;

export const isTerminalProcessingStatus = (status: ProcessingStatus | null | undefined): boolean =>
	status === ProcessingStatus.completed ||
	status === ProcessingStatus.failed ||
	status === ProcessingStatus.superseded;

export const isNonTerminalProcessingStatus = (status: ProcessingStatus | null | undefined): boolean =>
	status === ProcessingStatus.pending || status === ProcessingStatus.processing;

const mergeStatus = (
	current: ProcessingStatusResource | null | undefined,
	next: ProcessingStatusResource,
): ProcessingStatusResource => ({ ...(current ?? {}), ...next });

const patchListItem = (item: ArticleResource, uuid: string, next: ProcessingStatusResource): ArticleResource =>
	item.uuid === uuid ? { ...item, processing_status: mergeStatus(item.processing_status, next) } : item;

type ProcessingCacheClient = Pick<
	QueryClient,
	'getQueryData' | 'getQueriesData' | 'setQueryData' | 'setQueriesData' | 'invalidateQueries'
>;

/**
 * The highest sequence already applied for this article, from whichever cache entry holds it.
 * A list-only view (the dashboard) never populates the detail entry, so both are consulted.
 */
const appliedSequence = (queryClient: ProcessingCacheClient, articleUuid: string): number | null => {
	const detail = queryClient.getQueryData<ArticleDetailResource>(articleKeys.detail(articleUuid));

	if (typeof detail?.processing_status?.sequence === 'number') {
		return detail.processing_status.sequence;
	}

	const lists = queryClient.getQueriesData<InfiniteData<ArticleListResource>>({ queryKey: articleKeys.lists() });

	for (const [, list] of lists) {
		for (const page of list?.pages ?? []) {
			for (const item of page.items) {
				if (item.uuid === articleUuid && typeof item.processing_status?.sequence === 'number') {
					return item.processing_status.sequence;
				}
			}
		}
	}

	return null;
};

/**
 * Apply one status payload to every cache entry that shows the article.
 *
 * Events can arrive out of order: a socket reconnect can replay, and the polling fallback can
 * land a fresher state while an older event is still in flight. `sequence` only ever goes up
 * for a row (#261), so an event that is not newer than what the cache already holds is dropped
 * whole — including its invalidation, which would otherwise refetch on stale news.
 *
 * A terminal status also invalidates the detail, because the server-side entity changes at the
 * end of processing (attached kanjis and words, JLPT counters) and the payload only carries the
 * status row, not those results.
 */
export const applyProcessingStatus = (
	queryClient: ProcessingCacheClient,
	articleUuid: string,
	payload: ProcessingStatusResource,
): void => {
	const applied = appliedSequence(queryClient, articleUuid);

	if (applied !== null && typeof payload.sequence === 'number' && payload.sequence <= applied) {
		return;
	}

	queryClient.setQueryData<ArticleDetailResource>(articleKeys.detail(articleUuid), (detail) =>
		detail ? { ...detail, processing_status: mergeStatus(detail.processing_status, payload) } : detail,
	);

	queryClient.setQueriesData<InfiniteData<ArticleListResource>>({ queryKey: articleKeys.lists() }, (list) => {
		if (!list?.pages) return list;

		return {
			...list,
			pages: list.pages.map((page) => ({
				...page,
				items: page.items.map((item) => patchListItem(item, articleUuid, payload)),
			})),
		};
	});

	if (isTerminalProcessingStatus(payload.status)) {
		void queryClient.invalidateQueries({ queryKey: articleKeys.detail(articleUuid) });
	}
};
