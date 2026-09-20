/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { articleKanjis, articleWords } from '@/api/generated/article/article';
import type { ArticleKanjiListResource } from '@/api/generated/model/articleKanjiListResource';
import type { ArticleWordListResource } from '@/api/generated/model/articleWordListResource';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { ArticleAttachments } from './index';

vi.mock('@/api/generated/article/article', () => ({
	articleKanjis: vi.fn(),
	articleWords: vi.fn(),
	getArticleIndexQueryKey: (params?: unknown) => ['/articles', ...(params ? [params] : [])],
	getArticleShowQueryKey: (uid: string, params?: unknown) => [`/articles/${uid}`, ...(params ? [params] : [])],
	getArticleKanjisQueryKey: (uid: string, params?: unknown) => [
		`/articles/${uid}/kanjis`,
		...(params ? [params] : []),
	],
	getArticleWordsQueryKey: (uid: string, params?: unknown) => [`/articles/${uid}/words`, ...(params ? [params] : [])],
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
	}) as unknown as ArticleKanjiListResource;

const wordPage = (surfaces: string[], page = 1, total = surfaces.length, hasMore = false) =>
	({
		items: surfaces.map((word, index) => ({
			id: index + 1,
			uuid: `word-${word}`,
			word,
			furigana: 'べんきょう',
		})),
		pagination: pagination(page, total, hasMore),
	}) as unknown as ArticleWordListResource;

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
		vi.mocked(articleKanjis).mockResolvedValue(kanjiPage([]));
		vi.mocked(articleWords).mockResolvedValue(wordPage([]));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('No kanji have been attached to this article yet.');
		});
		expect(container.textContent).toContain('No vocabulary has been attached to this article yet.');
		expect(container.querySelectorAll('button')).toHaveLength(0);

		await unmount();
	});

	it('renders the first page and its totals', async () => {
		vi.mocked(articleKanjis).mockResolvedValue(kanjiPage(['水', '火']));
		vi.mocked(articleWords).mockResolvedValue(wordPage(['勉強']));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('水');
		});
		expect(container.textContent).toContain('火');
		expect(container.textContent).toContain('勉強');
		expect(container.querySelectorAll('li')).toHaveLength(3);
		expect(articleKanjis).toHaveBeenCalledWith(UUID, { page: 1, per_page: 20 }, undefined, expect.anything());

		await unmount();
	});

	it('asks for the next page only when the server says there is one', async () => {
		vi.mocked(articleKanjis)
			.mockResolvedValueOnce(kanjiPage(['水'], 1, 2, true))
			.mockResolvedValueOnce(kanjiPage(['火'], 2, 2, false));
		vi.mocked(articleWords).mockResolvedValue(wordPage([]));

		const { container, flush, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.querySelector('button')).not.toBeNull();
		});

		const loadMore = requireElement<HTMLButtonElement>(container, 'button');
		expect(loadMore.textContent).toContain('Show more kanji');

		await flush(() => {
			loadMore.click();
		});

		expect(articleKanjis).toHaveBeenNthCalledWith(
			2,
			UUID,
			{ page: 2, per_page: 20 },
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
		vi.mocked(articleKanjis).mockRejectedValue(new Error('network'));
		vi.mocked(articleWords).mockResolvedValue(wordPage(['勉強']));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('Kanji could not be loaded.');
		});
		expect(container.textContent).not.toContain('No kanji have been attached');

		await unmount();
	});
});
