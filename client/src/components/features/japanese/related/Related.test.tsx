/**
 * @vitest-environment jsdom
 */
import type * as React from 'react';
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { articleIndex } from '@/api/generated/article/article';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import { sentenceIndex } from '@/api/generated/sentence/sentence';
import { wordIndex } from '@/api/generated/word/word';
import { repeatRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct } from '@/test/renderWithAct';
import { EntryKanji, KanjiRelated, RadicalKanji, RELATED_PREVIEW_ROWS, WordRelated } from './';
import {
	articleRows,
	indexPage,
	kanjiPerson,
	personSentenceRows,
	personWordRows,
	radicalTree,
	sentenceLong,
	treeKanjiRows,
	wordKanaOnly,
	wordManyKanji,
} from './dictionaryDetail.fixtures';

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

/** An index of `total` rows built from fixtures, honouring `page`, `per_page` and a keyword (2 matches). */
const serve =
	<Row extends { id: number; uuid: string }>(fixtures: Row[], total: number) =>
	async ({
		page = 1,
		per_page = 20,
		keyword,
		q,
	}: {
		page?: number;
		per_page?: number;
		keyword?: string;
		q?: string;
	}) => {
		const size = keyword || q ? 2 : total;
		const count = Math.max(0, Math.min(per_page, size - (page - 1) * per_page));

		return indexPage(repeatRows(fixtures, count), size, per_page, page);
	};

const render = (ui: React.ReactElement) => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

	return renderWithAct(
		<QueryClientProvider client={queryClient}>
			<MemoryRouter>{ui}</MemoryRouter>
		</QueryClientProvider>,
	);
};

const button = (root: ParentNode, name: string | RegExp) =>
	Array.from(root.querySelectorAll('button')).find((element) => {
		const label = element.getAttribute('aria-label') ?? element.textContent ?? '';
		return typeof name === 'string' ? label === name : name.test(label);
	});

const headings = (root: ParentNode) => Array.from(root.querySelectorAll('h2')).map((heading) => heading.textContent);

describe('dictionary related sections', () => {
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

	describe('KanjiRelated', () => {
		const serveAll = (words: number, sentences: number, articles: number) => {
			vi.mocked(wordIndex).mockImplementation(serve(personWordRows, words) as never);
			vi.mocked(sentenceIndex).mockImplementation(serve(personSentenceRows, sentences) as never);
			vi.mocked(articleIndex).mockImplementation(serve(articleRows, articles) as never);
		};

		it('previews five of each list under its heading and count, with See all', async () => {
			serveAll(2398, 49419, 2);

			const { container, unmount } = await render(<KanjiRelated kanji={kanjiPerson} showSave />);

			await vi.waitFor(() => expect(button(container, 'See all 2,398 words')).toBeDefined());
			expect(headings(container)).toEqual(['Words with 人2,398', 'Sentences with 人49,419', 'Articles with 人2']);
			expect(button(container, 'See all 49,419 sentences')).toBeDefined();
			expect(button(container, 'See all 2 articles')).toBeDefined();
			expect(container.querySelectorAll('table[aria-label="Words"] tbody tr')).toHaveLength(RELATED_PREVIEW_ROWS);
			expect(container.querySelectorAll('table[aria-label="Sentences"] tbody tr')).toHaveLength(
				RELATED_PREVIEW_ROWS,
			);
			expect(container.querySelectorAll('a[href^="/articles/"]')).toHaveLength(2);
			expect(wordIndex).toHaveBeenCalledWith(
				{ kanji_uuid: kanjiPerson.uuid, per_page: 20, page: 1, include: 'viewer_catalogue_state' },
				undefined,
				expect.anything(),
			);
			expect(articleIndex).toHaveBeenCalledWith(
				{ 'kanji_ids[]': [kanjiPerson.id], per_page: 20, page: 1, include_stats_counts: true },
				undefined,
				expect.anything(),
			);

			await unmount();
		});

		it('gives signed-in viewers a Save column on word rows only', async () => {
			serveAll(30, 30, 0);

			const signedIn = await render(<KanjiRelated kanji={kanjiPerson} showSave />);
			await vi.waitFor(() =>
				expect(signedIn.container.querySelector('table[aria-label="Words"]')).not.toBeNull(),
			);
			expect(
				signedIn.container.querySelector(
					'table[aria-label="Words"] th[data-column="save"], table[aria-label="Words"] [aria-label^="Save "]',
				),
			).not.toBeNull();
			expect(signedIn.container.querySelector('table[aria-label="Sentences"] [aria-label^="Save "]')).toBeNull();
			await signedIn.unmount();

			const guest = await render(<KanjiRelated kanji={kanjiPerson} showSave={false} />);
			await vi.waitFor(() => expect(guest.container.querySelector('table[aria-label="Words"]')).not.toBeNull());
			expect(guest.container.querySelector('[aria-label^="Save "]')).toBeNull();
			await guest.unmount();
		});

		it('keeps every heading with one line when nothing uses the kanji', async () => {
			serveAll(0, 0, 0);

			const { container, unmount } = await render(<KanjiRelated kanji={kanjiPerson} showSave />);

			await vi.waitFor(() => expect(container.textContent).toContain('No words use this kanji yet.'));
			expect(container.textContent).toContain('No sentences use this kanji yet.');
			expect(container.textContent).toContain('No articles use this kanji yet.');
			expect(headings(container)).toHaveLength(3);
			expect(button(container, /^See all/)).toBeUndefined();

			await unmount();
		});

		it('reports a failed list in its own words', async () => {
			serveAll(3, 3, 0);
			vi.mocked(sentenceIndex).mockRejectedValue(new Error('SQLSTATE[08006] connection refused'));

			const { container, unmount } = await render(<KanjiRelated kanji={kanjiPerson} showSave={false} />);

			await vi.waitFor(() =>
				expect(container.textContent).toContain(
					'Sentences with 人 could not be loaded. Reload the page to try again.',
				),
			);
			expect(container.textContent).not.toContain('SQLSTATE');

			await unmount();
		});

		it('opens a paged, searchable modal whose search leaves the preview alone', async () => {
			serveAll(2398, 10, 0);

			const { container, flush, unmount } = await render(<KanjiRelated kanji={kanjiPerson} showSave={false} />);

			await vi.waitFor(() => expect(button(container, 'See all 2,398 words')).toBeDefined());
			await flush(() => button(container, 'See all 2,398 words')?.click());

			const dialog = () => document.getElementById('kanji-words-modal') as HTMLElement;
			await vi.waitFor(() => expect(dialog().querySelector('table[aria-label="Words"]')).not.toBeNull());
			expect(dialog().textContent).toContain('Showing 1–20 of 2,398 words');

			await flush(() => button(dialog(), 'Page 2')?.click());
			await vi.waitFor(() => expect(dialog().textContent).toContain('Showing 21–40 of 2,398 words'));

			const form = dialog().querySelector('form[role="search"]') as HTMLFormElement;
			await flush(() => typeInto(form.querySelector('input') as HTMLInputElement, '人間'));
			await flush(() => submitForm(form));
			await vi.waitFor(() => expect(dialog().textContent).toContain('Showing 1–2 of 2 words'));
			expect(wordIndex).toHaveBeenLastCalledWith(
				{
					kanji_uuid: kanjiPerson.uuid,
					per_page: 20,
					page: 1,
					keyword: '人間',
					include: 'viewer_catalogue_state',
				},
				undefined,
				expect.anything(),
			);

			const previewRows = Array.from(container.querySelectorAll('table[aria-label="Words"] tbody tr')).filter(
				(row) => !row.closest('dialog'),
			);
			expect(button(container, 'See all 2,398 words')).toBeDefined();
			expect(previewRows).toHaveLength(RELATED_PREVIEW_ROWS);

			await unmount();
		});

		it('sends an article search to the index as q', async () => {
			serveAll(0, 0, 30);

			const { container, flush, unmount } = await render(<KanjiRelated kanji={kanjiPerson} showSave={false} />);

			await vi.waitFor(() => expect(button(container, 'See all 30 articles')).toBeDefined());
			await flush(() => button(container, 'See all 30 articles')?.click());

			const dialog = () => document.getElementById('kanji-articles-modal') as HTMLElement;
			await vi.waitFor(() => expect(dialog().querySelector('form[role="search"]')).not.toBeNull());
			const form = dialog().querySelector('form[role="search"]') as HTMLFormElement;
			await flush(() => typeInto(form.querySelector('input') as HTMLInputElement, 'ニュース'));
			await flush(() => submitForm(form));

			await vi.waitFor(() =>
				expect(articleIndex).toHaveBeenLastCalledWith(
					{
						'kanji_ids[]': [kanjiPerson.id],
						per_page: 20,
						page: 1,
						include_stats_counts: true,
						q: 'ニュース',
					},
					undefined,
					expect.anything(),
				),
			);

			await unmount();
		});
	});

	describe('WordRelated', () => {
		it('lists every kanji in the order they appear, then the articles', async () => {
			vi.mocked(articleIndex).mockImplementation(serve(articleRows, 2) as never);

			const { container, unmount } = await render(
				<WordRelated word={{ ...wordManyKanji, kanjis: wordManyKanji.kanjis ?? [] }} />,
			);

			const glyphs = Array.from(
				container.querySelectorAll('table[aria-label="Kanji"] tbody [href^="/kanji/"]'),
			).map((link) => link.textContent);
			expect(glyphs).toEqual(Array.from(new Set(Array.from('公共土木施設災害復旧事業費国庫負担法'))));
			expect(container.querySelector('[aria-label^="Save "]')).toBeNull();
			await vi.waitFor(() => expect(button(container, 'See all 2 articles')).toBeDefined());
			expect(articleIndex).toHaveBeenCalledWith(
				{ 'word_ids[]': [wordManyKanji.id], per_page: 20, page: 1, include_stats_counts: true },
				undefined,
				expect.anything(),
			);

			await unmount();
		});

		it('says a kana word has no kanji', async () => {
			vi.mocked(articleIndex).mockImplementation(serve(articleRows, 0) as never);

			const { container, unmount } = await render(<WordRelated word={{ ...wordKanaOnly, kanjis: [] }} />);

			expect(container.textContent).toContain('This word is written without kanji.');
			await vi.waitFor(() => expect(container.textContent).toContain('No articles use this word yet.'));

			await unmount();
		});
	});

	describe('RadicalKanji', () => {
		it('pages and searches the radical’s kanji inline, 25 to a page, with Save', async () => {
			vi.mocked(kanjiIndex).mockImplementation(serve(treeKanjiRows, 674) as never);

			const { container, flush, unmount } = await render(
				<RadicalKanji radicalUuid={radicalTree.uuid} showSave />,
			);

			await vi.waitFor(() => expect(container.textContent).toContain('Showing 1–25 of 674 kanji'));
			expect(headings(container)).toEqual(['Kanji with this radical674']);
			expect(container.querySelector('[aria-label^="Save "]')).not.toBeNull();
			expect(document.querySelector('dialog')).toBeNull();

			await flush(() => button(container, 'Page 27')?.click());
			await vi.waitFor(() => expect(container.textContent).toContain('Showing 651–674 of 674 kanji'));
			expect(kanjiIndex).toHaveBeenLastCalledWith(
				{ radical_uuid: radicalTree.uuid, per_page: 25, page: 27, include: 'viewer_catalogue_state' },
				undefined,
				expect.anything(),
			);

			await unmount();
		});

		it('reports a failed list in its own words', async () => {
			vi.mocked(kanjiIndex).mockRejectedValue(new Error('SQLSTATE[08006] connection refused'));

			const { container, unmount } = await render(
				<RadicalKanji radicalUuid={radicalTree.uuid} showSave={false} />,
			);

			await vi.waitFor(() =>
				expect(container.textContent).toContain('The kanji could not be loaded. Reload the page to try again.'),
			);
			expect(container.textContent).not.toContain('SQLSTATE');

			await unmount();
		});
	});

	describe('EntryKanji', () => {
		it('lists a sentence’s 59 kanji without a Save column', async () => {
			const { container, unmount } = await render(
				<EntryKanji
					title="Kanji in this sentence"
					text={sentenceLong.content}
					kanjis={sentenceLong.kanjis ?? []}
					emptyMessage="This sentence is written without kanji."
				/>,
			);

			expect(headings(container)).toEqual(['Kanji in this sentence59']);
			expect(container.querySelectorAll('table[aria-label="Kanji"] tbody tr')).toHaveLength(59);
			expect(container.querySelector('[aria-label^="Save "]')).toBeNull();

			await unmount();
		});
	});
});
