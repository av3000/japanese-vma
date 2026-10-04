/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { wordIndex } from '@/api/generated/word/word';
import { useInfiniteWords } from '@/api/words/hooks/useInfiniteWords';
import { renderWithAct } from '@/test/renderWithAct';
import { articleWordFilters } from './attachments';
import { kanjiCountOf, useArticleReadingStats, type ArticleReadingStats } from './readingStats';

vi.mock('@/api/generated/word/word', () => ({
	wordIndex: vi.fn(),
	getWordIndexQueryKey: (params?: unknown) => ['/words', ...(params ? [params] : [])],
}));

const UUID = 'a1a1a1a1-0000-4000-8000-000000000001';
const LEVELS = { n1: 1, n2: 2, n3: 3, n4: 4, n5: 5, uncommon: 156 };

const status = (value: ProcessingStatus): ProcessingStatusResource => ({
	id: 1,
	entity_id: UUID,
	type: 'article_content_processing',
	status: value,
	sequence: 1,
	attempt: 1,
	max_attempts: 3,
	metadata: {},
	created_at: '2026-10-03T10:00:00+00:00',
	updated_at: '2026-10-03T10:00:05+00:00',
});

type Article = Pick<ArticleDetailResource, 'uid' | 'jlpt_levels' | 'processing_status'>;

const article = (processing: ProcessingStatus | null): Article => ({
	uid: UUID,
	jlpt_levels: LEVELS,
	processing_status: processing ? status(processing) : null,
});

/** Mounts the stats hook beside a second reader of the words list, as the page does. */
const renderStats = async (subject: Article) => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
	const seen: { stats?: ArticleReadingStats } = {};

	const Stats = () => {
		seen.stats = useArticleReadingStats(subject);
		return null;
	};
	const Table = () => {
		useInfiniteWords({ filters: articleWordFilters(UUID) });
		return null;
	};

	const view = await renderWithAct(
		<QueryClientProvider client={queryClient}>
			<Stats />
			<Table />
		</QueryClientProvider>,
	);

	return { ...view, seen };
};

describe('kanjiCountOf', () => {
	it('sums every level, uncommon included', () => {
		expect(kanjiCountOf(LEVELS)).toBe(171);
		expect(kanjiCountOf({ n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 })).toBe(0);
		expect(kanjiCountOf(null)).toBe(0);
	});
});

describe('useArticleReadingStats', () => {
	afterEach(() => {
		vi.resetAllMocks();
		document.body.innerHTML = '';
	});

	it('reads the kanji from the payload and the words from the shared list, in one request', async () => {
		vi.mocked(wordIndex).mockResolvedValue({
			items: [],
			pagination: { page: 1, per_page: 20, total: 233, last_page: 12, has_more: true },
		});

		const { seen, unmount } = await renderStats(article(ProcessingStatus.completed));

		await vi.waitFor(() => expect(seen.stats?.words).toBe(233));
		expect(seen.stats?.kanji).toBe(171);
		expect(wordIndex).toHaveBeenCalledTimes(1);

		await unmount();
	});

	it.each([ProcessingStatus.pending, ProcessingStatus.processing])('counts nothing while %s', async (value) => {
		vi.mocked(wordIndex).mockResolvedValue({
			items: [],
			pagination: { page: 1, per_page: 20, total: 0, last_page: 1, has_more: false },
		});

		const { seen, unmount } = await renderStats(article(value));

		await vi.waitFor(() => expect(wordIndex).toHaveBeenCalled());
		expect(seen.stats).toEqual({ kanji: null, words: null });

		await unmount();
	});

	it('leaves the word count out when the list cannot be read', async () => {
		vi.mocked(wordIndex).mockRejectedValue(new Error('offline'));

		const { seen, unmount } = await renderStats(article(null));

		await vi.waitFor(() => expect(seen.stats?.words).toBeUndefined());
		expect(seen.stats?.kanji).toBe(171);

		await unmount();
	});
});
