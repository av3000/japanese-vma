/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { articleIndex } from '@/api/generated/article/article';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import { sentenceIndex } from '@/api/generated/sentence/sentence';
import { wordIndex } from '@/api/generated/word/word';
import { renderWithAct } from '@/test/renderWithAct';
import { toArticleIndexParams, useKanjiArticlePages, useWordArticlePages } from './articles/related';
import { useKanjiSentencePages, useKanjiWordPages } from './kanjis/related';
import type { PagedIndex } from './pagedIndex';
import { useRadicalKanjiPages } from './radicals/related';

vi.mock('@/api/generated/word/word', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/api/generated/word/word')>()),
	wordIndex: vi.fn(),
}));
vi.mock('@/api/generated/sentence/sentence', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/api/generated/sentence/sentence')>()),
	sentenceIndex: vi.fn(),
}));
vi.mock('@/api/generated/kanji/kanji', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/api/generated/kanji/kanji')>()),
	kanjiIndex: vi.fn(),
}));
vi.mock('@/api/generated/article/article', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/api/generated/article/article')>()),
	articleIndex: vi.fn(),
}));

const emptyPage = { items: [], pagination: { page: 1, per_page: 20, total: 0, last_page: 1, has_more: false } };

/** Mounts one hook and returns its latest value. */
const mount = async (useHook: () => PagedIndex<unknown>) => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
	const seen: { list?: PagedIndex<unknown> } = {};
	const Probe = () => {
		seen.list = useHook();
		return null;
	};
	const view = await renderWithAct(
		<QueryClientProvider client={queryClient}>
			<Probe />
		</QueryClientProvider>,
	);

	return { seen, view };
};

describe('dictionary related lists', () => {
	beforeEach(() => {
		for (const index of [wordIndex, sentenceIndex, kanjiIndex, articleIndex]) {
			vi.mocked(index).mockResolvedValue(emptyPage as never);
		}
	});

	afterEach(() => {
		vi.clearAllMocks();
		document.body.innerHTML = '';
	});

	it('reads a kanji’s words by kanji_uuid with viewer save state, 20 to a page', async () => {
		const { view } = await mount(() => useKanjiWordPages('kanji-uuid'));

		await vi.waitFor(() =>
			expect(wordIndex).toHaveBeenCalledWith(
				{ kanji_uuid: 'kanji-uuid', per_page: 20, page: 1, include: 'viewer_catalogue_state' },
				undefined,
				expect.anything(),
			),
		);
		await view.unmount();
	});

	it('reads a kanji’s sentences by kanji_uuid, and searches them', async () => {
		const { seen, view } = await mount(() => useKanjiSentencePages('kanji-uuid'));

		await vi.waitFor(() => expect(sentenceIndex).toHaveBeenCalled());
		await view.flush(() => seen.list?.setKeyword('冷たい'));
		await vi.waitFor(() =>
			expect(sentenceIndex).toHaveBeenLastCalledWith(
				{ kanji_uuid: 'kanji-uuid', per_page: 20, page: 1, keyword: '冷たい' },
				undefined,
				expect.anything(),
			),
		);
		await view.unmount();
	});

	it('reads a radical’s kanji by radical_uuid with viewer save state, 25 to a page', async () => {
		const { seen, view } = await mount(() => useRadicalKanjiPages('radical-uuid'));

		await vi.waitFor(() => expect(kanjiIndex).toHaveBeenCalled());
		await view.flush(() => seen.list?.setPage(3));
		await vi.waitFor(() =>
			expect(kanjiIndex).toHaveBeenLastCalledWith(
				{ radical_uuid: 'radical-uuid', per_page: 25, page: 3, include: 'viewer_catalogue_state' },
				undefined,
				expect.anything(),
			),
		);
		await view.unmount();
	});

	it('reads a kanji’s articles by kanji_ids[] and sends the search as q', async () => {
		const { seen, view } = await mount(() => useKanjiArticlePages(898));

		await vi.waitFor(() =>
			expect(articleIndex).toHaveBeenCalledWith(
				{ 'kanji_ids[]': [898], per_page: 20, page: 1, include_stats_counts: true },
				undefined,
				expect.anything(),
			),
		);
		await view.flush(() => seen.list?.setKeyword('ニュース'));
		await vi.waitFor(() =>
			expect(articleIndex).toHaveBeenLastCalledWith(
				{ 'kanji_ids[]': [898], per_page: 20, page: 1, include_stats_counts: true, q: 'ニュース' },
				undefined,
				expect.anything(),
			),
		);
		await view.unmount();
	});

	it('reads a word’s articles by word_ids[], and waits for the word id', async () => {
		const pending = await mount(() => useWordArticlePages(undefined));
		expect(articleIndex).not.toHaveBeenCalled();
		await pending.view.unmount();

		const { view } = await mount(() => useWordArticlePages(31723));
		await vi.waitFor(() =>
			expect(articleIndex).toHaveBeenCalledWith(
				{ 'word_ids[]': [31723], per_page: 20, page: 1, include_stats_counts: true },
				undefined,
				expect.anything(),
			),
		);
		await view.unmount();
	});
});

describe('toArticleIndexParams', () => {
	it('renames the keyword to q and leaves it out when empty', () => {
		expect(toArticleIndexParams({ per_page: 20, keyword: 'x' })).toEqual({ per_page: 20, q: 'x' });
		expect(toArticleIndexParams({ per_page: 20 })).toEqual({ per_page: 20 });
	});
});
