/**
 * @vitest-environment jsdom
 */
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import type { WordResource } from '@/api/generated/model/wordResource';
import { wordIndex } from '@/api/generated/word/word';
import { kanjiRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { renderWithAct } from '@/test/renderWithAct';
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

const kanjiPage = (items: KanjiResource[], page = 1, total = items.length, hasMore = false): KanjiListResource => ({
	items,
	pagination: pagination(page, total, hasMore),
});

const wordPage = (items: WordResource[], page = 1, total = items.length, hasMore = false): WordListResource => ({
	items,
	pagination: pagination(page, total, hasMore),
});

const renderAttachments = async (props: { showSave?: boolean; isProcessing?: boolean } = {}) => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

	return renderWithAct(
		<QueryClientProvider client={queryClient}>
			<MemoryRouter>
				<ArticleAttachments
					articleUuid={UUID}
					showSave={props.showSave ?? false}
					isProcessing={props.isProcessing}
				/>
			</MemoryRouter>
		</QueryClientProvider>,
	);
};

const buttons = (container: HTMLElement) => Array.from(container.querySelectorAll('button'));

describe('ArticleAttachments', () => {
	beforeEach(() => {
		vi.resetAllMocks();
	});

	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('renders the kanji and words as the dictionary tables, with their totals', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(kanjiPage(kanjiRows.slice(0, 2), 1, 171));
		vi.mocked(wordIndex).mockResolvedValue(wordPage(wordRows.slice(0, 1), 1, 233));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain(kanjiRows[1].character);
		});
		expect(container.querySelector('table[aria-label="Kanji"]')).not.toBeNull();
		expect(container.querySelector('table[aria-label="Words"]')).not.toBeNull();
		expect(container.querySelector('h2')?.textContent).toBe('Kanji and words in this reading');
		expect(container.textContent).toContain('171');
		expect(container.textContent).toContain('233');
		expect(kanjiIndex).toHaveBeenCalledWith(
			{ article_uuid: UUID, per_page: 20, include: 'viewer_catalogue_state', page: 1 },
			undefined,
			expect.anything(),
		);

		await unmount();
	});

	it('adds the Save column for signed-in viewers only', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(kanjiPage(kanjiRows.slice(0, 1)));
		vi.mocked(wordIndex).mockResolvedValue(wordPage([]));

		const guest = await renderAttachments({ showSave: false });
		await vi.waitFor(() => expect(guest.container.textContent).toContain(kanjiRows[0].character));
		expect(buttons(guest.container).some((button) => button.getAttribute('aria-label')?.startsWith('Save'))).toBe(
			false,
		);
		await guest.unmount();

		const member = await renderAttachments({ showSave: true });
		await vi.waitFor(() => expect(member.container.textContent).toContain(kanjiRows[0].character));
		expect(
			buttons(member.container).some((button) => /^Saved?:? /.test(button.getAttribute('aria-label') ?? '')),
		).toBe(true);
		await member.unmount();
	});

	it('says plainly when processing attached nothing, and why while it is still running', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(kanjiPage([]));
		vi.mocked(wordIndex).mockResolvedValue(wordPage([]));

		const done = await renderAttachments();
		await vi.waitFor(() => {
			expect(done.container.textContent).toContain('No kanji have been attached to this article yet.');
		});
		expect(done.container.textContent).toContain('No words have been attached to this article yet.');
		expect(done.container.textContent).not.toContain('once processing finishes');
		await done.unmount();

		const running = await renderAttachments({ isProcessing: true });
		await vi.waitFor(() => {
			expect(running.container.textContent).toContain('They appear here once processing finishes.');
		});
		await running.unmount();
	});

	it('asks for the next page only when the server says there is one', async () => {
		vi.mocked(kanjiIndex)
			.mockResolvedValueOnce(kanjiPage(kanjiRows.slice(0, 1), 1, 2, true))
			.mockResolvedValueOnce(kanjiPage(kanjiRows.slice(1, 2), 2, 2, false));
		vi.mocked(wordIndex).mockResolvedValue(wordPage([]));

		const { container, flush, unmount } = await renderAttachments();
		const showMore = () => buttons(container).find((button) => button.textContent === 'Show more kanji');

		await vi.waitFor(() => expect(showMore()).toBeDefined());

		await flush(() => {
			showMore()?.click();
		});

		expect(kanjiIndex).toHaveBeenNthCalledWith(
			2,
			{ article_uuid: UUID, per_page: 20, include: 'viewer_catalogue_state', page: 2 },
			undefined,
			expect.anything(),
		);
		await vi.waitFor(() => {
			expect(container.textContent).toContain(kanjiRows[1].character);
		});
		expect(showMore()).toBeUndefined();

		await unmount();
	});

	it('reports a failed list in its own words instead of pretending the article has none', async () => {
		vi.mocked(kanjiIndex).mockRejectedValue(new Error('SQLSTATE[08006] connection refused'));
		vi.mocked(wordIndex).mockResolvedValue(wordPage(wordRows.slice(0, 1)));

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => {
			expect(container.textContent).toContain('Kanji could not be loaded. Reload the page to try again.');
		});
		expect(container.textContent).not.toContain('No kanji have been attached');
		expect(container.textContent).not.toContain('SQLSTATE');

		await unmount();
	});
});
