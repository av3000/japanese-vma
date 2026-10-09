/**
 * @vitest-environment jsdom
 */
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import { wordIndex } from '@/api/generated/word/word';
import { kanjiRows, repeatRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { choose, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import { ArticleAttachments, PREVIEW_KANJI } from './index';

vi.mock('@/api/generated/kanji/kanji', () => ({
	kanjiIndex: vi.fn(),
	getKanjiIndexQueryKey: (params?: unknown) => ['/kanjis', ...(params ? [params] : [])],
}));

vi.mock('@/api/generated/word/word', () => ({
	wordIndex: vi.fn(),
	getWordIndexQueryKey: (params?: unknown) => ['/words', ...(params ? [params] : [])],
}));

const UUID = 'a1a1a1a1-0000-4000-8000-000000000001';
const FILTERS = { article_uuid: UUID, per_page: 20, include: 'viewer_catalogue_state' };

const page = <Row,>(items: Row[], pageNumber: number, total: number, perPage = 20) => ({
	items,
	pagination: {
		page: pageNumber,
		per_page: perPage,
		total,
		last_page: Math.ceil(total / perPage) || 1,
		has_more: pageNumber * perPage < total,
	},
});

/** An index of `total` rows built from fixtures, honouring `page`, `per_page` and a keyword (3 matches). */
const serve =
	<Row extends { id: number; uuid: string }>(fixtures: Row[], total: number) =>
	async ({
		page: pageNumber = 1,
		per_page = 20,
		keyword,
	}: {
		page?: number;
		per_page?: number;
		keyword?: string;
	}) => {
		const size = keyword ? 3 : total;

		return page(
			repeatRows(fixtures, Math.max(0, Math.min(per_page, size - (pageNumber - 1) * per_page))),
			pageNumber,
			size,
			per_page,
		);
	};

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

const button = (root: ParentNode, name: string | RegExp) =>
	Array.from(root.querySelectorAll('button')).find((element) => {
		const label = element.getAttribute('aria-label') ?? element.textContent ?? '';
		return typeof name === 'string' ? label === name : name.test(label);
	});

describe('ArticleAttachments', () => {
	beforeEach(() => {
		HTMLDialogElement.prototype.showModal = vi.fn(function (this: HTMLDialogElement) {
			this.setAttribute('open', '');
		});
		HTMLDialogElement.prototype.close = vi.fn(function (this: HTMLDialogElement) {
			this.removeAttribute('open');
		});
	});

	afterEach(() => {
		vi.resetAllMocks();
		document.body.innerHTML = '';
	});

	it('previews the first kanji and words, with the totals on the See all buttons', async () => {
		vi.mocked(kanjiIndex).mockImplementation(serve(kanjiRows, 171) as never);
		vi.mocked(wordIndex).mockImplementation(serve(wordRows, 233) as never);

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() => expect(button(container, 'See all 171 kanji')).toBeDefined());
		expect(button(container, 'See all 233 words')).toBeDefined();
		expect(container.querySelectorAll('a[href^="/kanji/"]')).toHaveLength(PREVIEW_KANJI);
		expect(container.querySelector('h2')?.textContent).toBe('Kanji and words in this reading');
		expect(container.querySelector('table')).toBeNull();
		expect(kanjiIndex).toHaveBeenCalledWith({ ...FILTERS, page: 1 }, undefined, expect.anything());

		await unmount();
	});

	it('says plainly when processing attached nothing, and why while it is still running', async () => {
		vi.mocked(kanjiIndex).mockImplementation(serve(kanjiRows, 0) as never);
		vi.mocked(wordIndex).mockImplementation(serve(wordRows, 0) as never);

		const done = await renderAttachments();
		await vi.waitFor(() =>
			expect(done.container.textContent).toContain('No kanji have been attached to this article yet.'),
		);
		expect(done.container.textContent).toContain('No words have been attached to this article yet.');
		expect(button(done.container, /^See all/)).toBeUndefined();
		await done.unmount();

		const running = await renderAttachments({ isProcessing: true });
		await vi.waitFor(() =>
			expect(running.container.textContent).toContain('They appear here once processing finishes.'),
		);
		await running.unmount();
	});

	it('reports a failed list in its own words', async () => {
		vi.mocked(kanjiIndex).mockRejectedValue(new Error('SQLSTATE[08006] connection refused'));
		vi.mocked(wordIndex).mockImplementation(serve(wordRows, 3) as never);

		const { container, unmount } = await renderAttachments();

		await vi.waitFor(() =>
			expect(container.textContent).toContain('Kanji could not be loaded. Reload the page to try again.'),
		);
		expect(container.textContent).not.toContain('SQLSTATE');

		await unmount();
	});

	it('opens the full list in a modal with numbered pages, a page size and a search', async () => {
		vi.mocked(kanjiIndex).mockImplementation(serve(kanjiRows, 171) as never);
		vi.mocked(wordIndex).mockImplementation(serve(wordRows, 3) as never);

		const { container, flush, unmount } = await renderAttachments({ showSave: true });

		await vi.waitFor(() => expect(button(container, 'See all 171 kanji')).toBeDefined());
		await flush(() => button(container, 'See all 171 kanji')?.click());

		const dialog = () => document.getElementById('article-kanji-modal') as HTMLElement;
		await vi.waitFor(() => expect(dialog().querySelector('table[aria-label="Kanji"]')).not.toBeNull());
		expect(dialog().textContent).toContain('Showing 1–20 of 171 kanji');
		expect(dialog().querySelector('nav[aria-label="Kanji pages"]')).not.toBeNull();
		expect(dialog().querySelector('[aria-current="page"]')?.textContent).toBe('1');

		await flush(() => button(dialog(), 'Page 2')?.click());
		await vi.waitFor(() => expect(dialog().textContent).toContain('Showing 21–40 of 171 kanji'));
		expect(kanjiIndex).toHaveBeenCalledWith({ ...FILTERS, page: 2 }, undefined, expect.anything());

		expect(button(dialog(), /^Load all/)).toBeUndefined();

		await flush(() => choose(dialog().querySelector('select') as HTMLSelectElement, '50'));
		await vi.waitFor(() => expect(dialog().textContent).toContain('Showing 1–50 of 171 kanji'));
		expect(kanjiIndex).toHaveBeenLastCalledWith(
			{ ...FILTERS, per_page: 50, page: 1 },
			undefined,
			expect.anything(),
		);

		const form = dialog().querySelector('form[role="search"]') as HTMLFormElement;
		await flush(() => typeInto(form.querySelector('input') as HTMLInputElement, ' water '));
		await flush(() => submitForm(form));
		await vi.waitFor(() => expect(dialog().textContent).toContain('Showing 1–3 of 3 kanji'));
		expect(kanjiIndex).toHaveBeenLastCalledWith(
			{ ...FILTERS, per_page: 50, page: 1, keyword: 'water' },
			undefined,
			expect.anything(),
		);

		await unmount();
	});
});
