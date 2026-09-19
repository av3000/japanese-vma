import { QueryClient, useQueryClient, type InfiniteData } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useEcho } from '@/lib/echo';
import { articleKeys } from '../keys';
import { ownerProcessingChannel, PROCESSING_STATUS_EVENT } from '../processingChannels';
import { useOwnerProcessingSubscription } from './useOwnerProcessingSubscription';

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useQueryClient: vi.fn() };
});

vi.mock('@/lib/echo', () => ({
	useEcho: vi.fn(),
}));

const OWNER = 'owner-uuid';
const A = 'a1a1a1a1-0000-4000-8000-000000000001';
const B = 'b2b2b2b2-0000-4000-8000-000000000002';

const payload = (
	entityId: string,
	status: ProcessingStatus,
	type = 'article_content_processing',
): ProcessingStatusResource => ({
	id: 3,
	entity_id: entityId,
	type,
	status,
	attempt: 1,
	metadata: {},
	created_at: '2026-09-20T10:00:00+00:00',
	updated_at: '2026-09-20T10:00:05+00:00',
});

const useHarness = () => {
	const queryClient = new QueryClient();
	vi.mocked(useQueryClient).mockReturnValue(queryClient);

	let listener!: (payload: ProcessingStatusResource | string) => void;
	vi.mocked(useEcho).mockImplementation(((_channel: string, _event: string, callback: typeof listener) => {
		listener = callback;
		return {} as never;
	}) as never);

	useOwnerProcessingSubscription(OWNER);

	const listKey = articleKeys.list({ author_uid: OWNER });
	queryClient.setQueryData<InfiniteData<ArticleListResource>>(listKey, {
		pageParams: [1],
		pages: [
			{
				items: [
					{ uuid: A, processing_status: payload(A, ProcessingStatus.pending) },
					{ uuid: B, processing_status: payload(B, ProcessingStatus.pending) },
				],
				facets: [],
				applied: {},
				pagination: { page: 1, has_more: false, total: 2 },
			} as unknown as ArticleListResource,
		],
	});

	const statusOf = (uuid: string) =>
		queryClient
			.getQueryData<InfiniteData<ArticleListResource>>(listKey)
			?.pages[0].items.find((item) => item.uuid === uuid)?.processing_status?.status;

	return { listener, statusOf };
};

describe('useOwnerProcessingSubscription', () => {
	beforeEach(() => {
		vi.mocked(useEcho).mockReset();
	});

	it('subscribes to the owner channel once', () => {
		useHarness();

		expect(useEcho).toHaveBeenCalledTimes(1);
		expect(useEcho).toHaveBeenCalledWith(
			ownerProcessingChannel(OWNER),
			PROCESSING_STATUS_EVENT,
			expect.any(Function),
			expect.any(Array),
			'private',
		);
	});

	it('patches the list item named by entity_id and leaves the others alone', () => {
		const { listener, statusOf } = useHarness();

		listener(payload(B, ProcessingStatus.completed));

		expect(statusOf(B)).toBe(ProcessingStatus.completed);
		expect(statusOf(A)).toBe(ProcessingStatus.pending);
	});

	it('ignores events of another task type', () => {
		const { listener, statusOf } = useHarness();

		listener(JSON.stringify(payload(A, ProcessingStatus.completed, 'kanji_extraction')));

		expect(statusOf(A)).toBe(ProcessingStatus.pending);
	});
});
