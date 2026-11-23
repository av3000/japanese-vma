/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { articleIndex } from '@/api/generated/article/article';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { renderWithAct } from '@/test/renderWithAct';
import { useInfiniteArticles } from './useInfiniteArticles';
import { PROCESSING_POLL_FAST_MS } from './useProcessingPolling';

vi.mock('@/api/generated/article/article', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/article/article')>(
		'@/api/generated/article/article',
	);
	return { ...actual, articleIndex: vi.fn() };
});

/**
 * No socket provider is mounted, so `useWebSocket` yields its default context: not configured,
 * `disconnected`. That is exactly the anonymous visitor on the public list.
 */
const page = (status: ProcessingStatus | null): ArticleListResource =>
	({
		items: [
			{
				uuid: 'u1',
				processing_status: status
					? {
							id: 1,
							type: 'kanji_extraction',
							status,
							metadata: {},
							created_at: '2026-09-19T10:00:00+00:00',
							updated_at: '2026-09-19T10:00:05+00:00',
						}
					: null,
			},
		],
		facets: [],
		applied: {},
		pagination: { page: 1, has_more: false, total: 1 },
	}) as unknown as ArticleListResource;

const Badge = () => {
	const { articles, isPending } = useInfiniteArticles({ filters: { per_page: 4 } });
	if (isPending) return <span data-testid="badge">loading</span>;
	return <span data-testid="badge">{articles[0]?.processing_status?.status ?? 'none'}</span>;
};

const mount = async () => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 5 * 60_000 } } });
	const rendered = await renderWithAct(
		<QueryClientProvider client={queryClient}>
			<Badge />
		</QueryClientProvider>,
	);
	const badge = () => rendered.container.querySelector('[data-testid="badge"]')?.textContent;
	return { ...rendered, badge, queryClient };
};

describe('processing status polling for an anonymous list reader', () => {
	beforeEach(() => {
		vi.useFakeTimers();
		vi.mocked(articleIndex).mockReset();
	});

	afterEach(() => {
		vi.useRealTimers();
	});

	it('shows the badge change after the server moves processing to completed', async () => {
		vi.mocked(articleIndex)
			.mockResolvedValueOnce(page(ProcessingStatus.processing))
			.mockResolvedValueOnce(page(ProcessingStatus.completed));

		const { badge, flush, unmount, queryClient } = await mount();
		await flush(async () => {
			await vi.advanceTimersByTimeAsync(0);
		});
		expect(badge()).toBe(ProcessingStatus.processing);
		expect(articleIndex).toHaveBeenCalledTimes(1);

		await flush(async () => {
			await vi.advanceTimersByTimeAsync(PROCESSING_POLL_FAST_MS + 50);
		});
		expect(articleIndex).toHaveBeenCalledTimes(2);
		expect(badge()).toBe(ProcessingStatus.completed);

		// Terminal now: a further window must not fetch again.
		await flush(async () => {
			await vi.advanceTimersByTimeAsync(20_000);
		});
		expect(articleIndex).toHaveBeenCalledTimes(2);

		await unmount();
		queryClient.clear();
	});

	it('does not poll at all in a 20 second window when nothing is non-terminal', async () => {
		vi.mocked(articleIndex).mockResolvedValue(page(ProcessingStatus.completed));

		const { badge, flush, unmount, queryClient } = await mount();
		await flush(async () => {
			await vi.advanceTimersByTimeAsync(0);
		});
		expect(badge()).toBe(ProcessingStatus.completed);

		await flush(async () => {
			await vi.advanceTimersByTimeAsync(20_000);
		});
		expect(articleIndex).toHaveBeenCalledTimes(1);

		await unmount();
		queryClient.clear();
	});
});
