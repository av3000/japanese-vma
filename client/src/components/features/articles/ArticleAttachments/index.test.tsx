/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import type { KanjiIndex200 } from '@/api/generated/model/kanjiIndex200';
import type { WordIndex200 } from '@/api/generated/model/wordIndex200';
import { wordIndex } from '@/api/generated/word/word';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { ArticleAttachments } from './index';

vi.mock('@/api/generated/kanji/kanji', () => ({
	kanjiIndex: vi.fn(),
	getKanjiIndexQueryKey: (params?: unknown) => ['/kanjis', ...(params ? [params] : [])],
}));

vi.mock('@/api/generated/word/word', () => ({
	wordIndex: vi.fn(),
	getWordIndexQueryKey: (params?: unknown) => ['/words', ...(params ? [params] : [])],
}));

const UUID = 'a1a1a1a1-0000-4000-8000-000000000001';

const pagination = (page: number, total: number, hasMore: boolean) => ({
	page,
	per_page: 20,
	total,
	last_page: hasMore ? page + 1 : page,
	has_more: hasMore,
});

const kanjiPage = (characters: string[], page = 1, total = characters.length, hasMore = false) =>
	({
		items: characters.map((character, index) => ({
			id: index + 1,
			uuid: `kanji-${character}`,
			character,
			meanings: 'water',
		})),
		pagination: pagination(page, total, hasMore),
	}) as unknown as KanjiIndex200;

const wordPage = (surfaces: string[], page = 1, total = surfaces.length, hasMore = false) =>
	({
		items: surfaces.map((word, index) => ({
			id: index + 1,
			uuid: `word-${word}`,
			word,
			furigana: 'べんきょう',
		})),
		pagination: pagination(page, total, hasMore),
	}) as unknown as WordIndex200;

const renderAttachments = async () => {
	const queryClient = new QueryClient({
		defaultOptions: { queries: { retry: false } },
	});

	return renderWithAct(
		<QueryClientProvider client={queryClient}>
			<ArticleAttachments articleUuid={UUID} />
		</QueryClientProvider>,
	);
};

describe('ArticleAttachments', () => {
	beforeEach(() => {
		vi.resetAllMocks();
	});

	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('says so plainly when processing has attached nothing', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(kanjiPage([]));
		vi.mocked(wordIndex).mockResolvedValue(wordPage([]));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('No kanji have been attached to this article yet.');
		});
		expect(container.textContent).toContain('No vocabulary has been attached to this article yet.');
		expect(container.querySelectorAll('button')).toHaveLength(0);

		await unmount();
	});

	it('renders the first page and its totals', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(kanjiPage(['水', '火']));
		vi.mocked(wordIndex).mockResolvedValue(wordPage(['勉強']));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('水');
		});
		expect(container.textContent).toContain('火');
		expect(container.textContent).toContain('勉強');
		expect(container.querySelectorAll('li')).toHaveLength(3);
		expect(kanjiIndex).toHaveBeenCalledWith(
			{ article_uuid: UUID, per_page: 20, page: 1 },
			undefined,
			expect.anything(),
		);

		await unmount();
	});

	it('asks for the next page only when the server says there is one', async () => {
		vi.mocked(kanjiIndex)
			.mockResolvedValueOnce(kanjiPage(['水'], 1, 2, true))
			.mockResolvedValueOnce(kanjiPage(['火'], 2, 2, false));
		vi.mocked(wordIndex).mockResolvedValue(wordPage([]));

		const { container, flush, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.querySelector('button')).not.toBeNull();
		});

		const loadMore = requireElement<HTMLButtonElement>(container, 'button');
		expect(loadMore.textContent).toContain('Show more kanji');

		await flush(() => {
			loadMore.click();
		});

		expect(kanjiIndex).toHaveBeenNthCalledWith(
			2,
			{ article_uuid: UUID, per_page: 20, page: 2 },
			undefined,
			expect.anything(),
		);
		await vi.waitFor(() => {
			expect(container.textContent).toContain('火');
		});
		expect(container.querySelectorAll('button')).toHaveLength(0);

		await unmount();
	});

	it('reports a failed page instead of pretending the article has none', async () => {
		vi.mocked(kanjiIndex).mockRejectedValue(new Error('network'));
		vi.mocked(wordIndex).mockResolvedValue(wordPage(['勉強']));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('Kanji could not be loaded.');
		});
		expect(container.textContent).not.toContain('No kanji have been attached');

		await unmount();
	});
});
