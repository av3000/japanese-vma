import { QueryClient, type InfiniteData } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { articleKeys } from './keys';
import {
	applyProcessingStatus,
	isNonTerminalProcessingStatus,
	isTerminalProcessingStatus,
} from './processingStatusCache';

const UUID = 'a1a1a1a1-0000-4000-8000-000000000001';
const OTHER_UUID = 'b2b2b2b2-0000-4000-8000-000000000002';

/** Seeded cache entries hold sequence 1, so an incoming event defaults to the newer 2. */
const status = (value: ProcessingStatus, sequence = 2): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'entity-uuid',
	attempt: 1,
	max_attempts: 3,
	sequence,
	type: 'kanji_extraction',
	status: value,
	metadata: {},
	created_at: '2026-09-19T10:00:00+00:00',
	updated_at: '2026-09-19T10:00:05+00:00',
});

const listItem = (uuid: string, processing: ProcessingStatusResource | null): ArticleResource =>
	({ uuid, title_jp: `Article ${uuid}`, processing_status: processing }) as unknown as ArticleResource;

const listPage = (items: ArticleResource[]): ArticleListResource =>
	({
		items,
		facets: [],
		applied: {},
		pagination: { page: 1, has_more: false, total: items.length },
	}) as unknown as ArticleListResource;

const detail = (processing: ProcessingStatusResource | null): ArticleDetailResource =>
	({ uid: UUID, title_jp: 'Detail', processing_status: processing }) as unknown as ArticleDetailResource;

const seed = () => {
	const queryClient = new QueryClient();
	const listKey = articleKeys.list({ per_page: 4, include_facets: false });
	const otherListKey = articleKeys.list({ q: 'school' });

	queryClient.setQueryData<InfiniteData<ArticleListResource>>(listKey, {
		pageParams: [1],
		pages: [listPage([listItem(UUID, status(ProcessingStatus.pending, 1)), listItem(OTHER_UUID, null)])],
	});
	queryClient.setQueryData<InfiniteData<ArticleListResource>>(otherListKey, {
		pageParams: [1],
		pages: [listPage([listItem(UUID, status(ProcessingStatus.pending, 1))])],
	});
	queryClient.setQueryData(articleKeys.detail(UUID), detail(status(ProcessingStatus.pending, 1)));

	return { queryClient, listKey, otherListKey };
};

describe('applyProcessingStatus', () => {
	it('patches the detail entry and every cached list variant that contains the article', () => {
		const { queryClient, listKey, otherListKey } = seed();

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.processing));

		expect(
			queryClient.getQueryData<ArticleDetailResource>(articleKeys.detail(UUID))?.processing_status?.status,
		).toBe(ProcessingStatus.processing);

		for (const key of [listKey, otherListKey]) {
			const items = queryClient.getQueryData<InfiniteData<ArticleListResource>>(key)?.pages[0].items ?? [];
			expect(items.find((item) => item.uuid === UUID)?.processing_status?.status).toBe(
				ProcessingStatus.processing,
			);
		}

		const untouched = queryClient
			.getQueryData<InfiniteData<ArticleListResource>>(listKey)
			?.pages[0].items.find((item) => item.uuid === OTHER_UUID);
		expect(untouched?.processing_status).toBeNull();
	});

	it('invalidates the detail only when the status is terminal', () => {
		const { queryClient } = seed();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.processing, 2));
		expect(invalidate).not.toHaveBeenCalled();

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.completed, 3));
		expect(invalidate).toHaveBeenCalledWith({ queryKey: articleKeys.detail(UUID) });

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.failed, 4));
		expect(invalidate).toHaveBeenCalledTimes(2);
	});

	it('leaves caches alone when nothing is loaded yet', () => {
		const queryClient = new QueryClient();

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.processing));

		expect(queryClient.getQueryData(articleKeys.detail(UUID))).toBeUndefined();
		expect(queryClient.getQueryCache().findAll({ queryKey: articleKeys.lists() })).toHaveLength(0);
	});

	it('ignores an event that is not newer than what the cache already holds', () => {
		const { queryClient, listKey } = seed();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');

		// Seeded at sequence 1; a replayed or reordered completion carrying sequence 1 is old news.
		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.completed, 1));

		expect(
			queryClient.getQueryData<ArticleDetailResource>(articleKeys.detail(UUID))?.processing_status?.status,
		).toBe(ProcessingStatus.pending);

		const item = queryClient
			.getQueryData<InfiniteData<ArticleListResource>>(listKey)
			?.pages[0].items.find((entry) => entry.uuid === UUID);
		expect(item?.processing_status?.status).toBe(ProcessingStatus.pending);
		expect(invalidate).not.toHaveBeenCalled();
	});

	it('compares against the list entry when no detail is loaded', () => {
		const { queryClient, listKey } = seed();
		queryClient.removeQueries({ queryKey: articleKeys.detail(UUID) });

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.failed, 1));

		const item = queryClient
			.getQueryData<InfiniteData<ArticleListResource>>(listKey)
			?.pages[0].items.find((entry) => entry.uuid === UUID);
		expect(item?.processing_status?.status).toBe(ProcessingStatus.pending);
	});

	it('never writes under the legacy hand-written keys', () => {
		const { queryClient } = seed();

		applyProcessingStatus(queryClient, UUID, status(ProcessingStatus.processing));

		expect(queryClient.getQueryData(['articles'])).toBeUndefined();
		expect(queryClient.getQueryData(['article', UUID])).toBeUndefined();
	});
});

describe('status predicates', () => {
	it('classify the four statuses', () => {
		expect(isTerminalProcessingStatus(ProcessingStatus.completed)).toBe(true);
		expect(isTerminalProcessingStatus(ProcessingStatus.failed)).toBe(true);
		expect(isTerminalProcessingStatus(ProcessingStatus.superseded)).toBe(true);
		expect(isNonTerminalProcessingStatus(ProcessingStatus.superseded)).toBe(false);
		expect(isTerminalProcessingStatus(ProcessingStatus.pending)).toBe(false);
		expect(isNonTerminalProcessingStatus(ProcessingStatus.pending)).toBe(true);
		expect(isNonTerminalProcessingStatus(ProcessingStatus.processing)).toBe(true);
		expect(isNonTerminalProcessingStatus(ProcessingStatus.completed)).toBe(false);
		expect(isNonTerminalProcessingStatus(null)).toBe(false);
		expect(isNonTerminalProcessingStatus(undefined)).toBe(false);
	});
});
