import { QueryClient, useQueryClient, type InfiniteData } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useEcho } from '@/lib/echo';
import { articleKeys } from '../keys';
import { useArticleSubscription } from './useArticleSubscription';

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useQueryClient: vi.fn() };
});

vi.mock('@/lib/echo', () => ({
	useEcho: vi.fn(),
}));

const UUID = 'a1a1a1a1-0000-4000-8000-000000000001';

const payload = (status: LastOperationStatus, type = 'article_content_processing'): ProcessingStatusResource => ({
	id: 9,
	entity_id: UUID,
	type,
	status,
	attempt: 1,
	metadata: { attempts: 1 },
	created_at: '2026-09-19T10:00:00+00:00',
	updated_at: '2026-09-19T10:00:05+00:00',
});

/**
 * Mounts nothing: `useEcho` is mocked to hand back the listener the hook registers, so the
 * test drives it exactly as Echo would. The cache underneath is real.
 */
const useSubscriptionHarness = () => {
	const queryClient = new QueryClient();
	vi.mocked(useQueryClient).mockReturnValue(queryClient);

	let listener!: (payload: ProcessingStatusResource | string) => void;
	vi.mocked(useEcho).mockImplementation(((_channel: string, _event: string, callback: typeof listener) => {
		listener = callback;
		return {} as never;
	}) as never);

	useArticleSubscription(UUID);

	return { queryClient, listener };
};

describe('useArticleSubscription', () => {
	beforeEach(() => {
		vi.mocked(useEcho).mockReset();
	});

	it('listens on the private last_operations channel for the aliased event', () => {
		useSubscriptionHarness();

		expect(useEcho).toHaveBeenCalledWith(
			`last_operations.${UUID}`,
			'.OperationStatusUpdated',
			expect.any(Function),
			expect.any(Array),
			'private',
		);
	});

	it('writes an event into the generated list and detail keys', () => {
		const { queryClient, listener } = useSubscriptionHarness();
		const listKey = articleKeys.list({ per_page: 4, include_facets: false });

		queryClient.setQueryData<InfiniteData<ArticleListResource>>(listKey, {
			pageParams: [1],
			pages: [
				{
					items: [{ uuid: UUID, processing_status: payload(LastOperationStatus.pending) }],
					facets: [],
					applied: {},
					pagination: { page: 1, has_more: false, total: 1 },
				} as unknown as ArticleListResource,
			],
		});
		queryClient.setQueryData(articleKeys.detail(UUID), {
			uid: UUID,
			processing_status: payload(LastOperationStatus.pending),
		} as unknown as ArticleDetailResource);

		listener(payload(LastOperationStatus.processing));

		expect(
			queryClient.getQueryData<ArticleDetailResource>(articleKeys.detail(UUID))?.processing_status?.status,
		).toBe(LastOperationStatus.processing);
		expect(
			queryClient.getQueryData<InfiniteData<ArticleListResource>>(listKey)?.pages[0].items[0].processing_status
				?.status,
		).toBe(LastOperationStatus.processing);
	});

	it('ignores an event whose type is not the consolidated task', () => {
		const { queryClient, listener } = useSubscriptionHarness();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
		const before = {
			uid: UUID,
			processing_status: payload(LastOperationStatus.pending),
		} as unknown as ArticleDetailResource;
		queryClient.setQueryData(articleKeys.detail(UUID), before);

		listener(payload(LastOperationStatus.completed, 'kanji_extraction'));

		expect(queryClient.getQueryData(articleKeys.detail(UUID))).toBe(before);
		expect(invalidate).not.toHaveBeenCalled();
	});

	it('accepts a JSON string payload and invalidates the detail on a terminal status', () => {
		const { queryClient, listener } = useSubscriptionHarness();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
		queryClient.setQueryData(articleKeys.detail(UUID), {
			uid: UUID,
			processing_status: null,
		} as unknown as ArticleDetailResource);

		listener(JSON.stringify(payload(LastOperationStatus.completed)));

		expect(
			queryClient.getQueryData<ArticleDetailResource>(articleKeys.detail(UUID))?.processing_status?.status,
		).toBe(LastOperationStatus.completed);
		expect(invalidate).toHaveBeenCalledWith({ queryKey: articleKeys.detail(UUID) });
	});
});
