/**
 * @vitest-environment jsdom
 */
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import { kanjiIndex } from '@/api/generated/kanji/kanji';
import { radicalIndex } from '@/api/generated/radical/radical';
import { sentenceIndex } from '@/api/generated/sentence/sentence';
import { wordIndex } from '@/api/generated/word/word';
import { kanjiRows, radicalRows, sentenceRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { renderWithAct } from '@/test/renderWithAct';
import { CatalogueItems } from './index';

vi.mock('@/api/generated/kanji/kanji', () => ({
	kanjiIndex: vi.fn(),
	getKanjiIndexQueryKey: (params?: unknown) => ['/kanjis', ...(params ? [params] : [])],
}));
vi.mock('@/api/generated/word/word', () => ({
	wordIndex: vi.fn(),
	getWordIndexQueryKey: (params?: unknown) => ['/words', ...(params ? [params] : [])],
}));
vi.mock('@/api/generated/radical/radical', () => ({
	radicalIndex: vi.fn(),
	getRadicalIndexQueryKey: (params?: unknown) => ['/radicals', ...(params ? [params] : [])],
}));
vi.mock('@/api/generated/sentence/sentence', () => ({
	sentenceIndex: vi.fn(),
	getSentenceIndexQueryKey: (params?: unknown) => ['/sentences', ...(params ? [params] : [])],
}));
vi.mock('@/api/generated/catalogue/catalogue', () => ({
	catalogueRemoveItem: vi.fn(),
	getCatalogueShowQueryKey: (uuid: string) => [`/catalogues/${uuid}`],
}));

const UUID = 'c1c1c1c1-0000-4000-8000-000000000001';
const TYPES = { radicals: 5, kanji: 6, words: 7, sentences: 8, articles: 9 } as const;

const page = <Row,>(items: Row[], hasMore = false, total = items.length) => ({
	items,
	pagination: { page: 1, per_page: 25, total, last_page: hasMore ? 2 : 1, has_more: hasMore },
});

const articleItem: CatalogueArticleItem = {
	id: 41,
	uuid: 'e2f6d1c0-1111-4222-8333-444455556666',
	title_jp: '日本語の記事',
	saves_count: 3,
	hashtags: [{ id: 1, content: 'grammar' }],
	engagement: { views_count: 12, downloads_count: 1, comments_count: 2, likes_count: 1 },
} as unknown as CatalogueArticleItem;

const renderItems = async (props: Partial<React.ComponentProps<typeof CatalogueItems>> & { catalogueType: number }) => {
	const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });

	return renderWithAct(
		<QueryClientProvider client={queryClient}>
			<MemoryRouter>
				<CatalogueItems catalogueUuid={UUID} payloadItems={[]} isOwner={false} showSave={false} {...props} />
			</MemoryRouter>
		</QueryClientProvider>,
	);
};

const button = (container: HTMLElement, name: string) =>
	Array.from(container.querySelectorAll('button')).find(
		(element) => (element.getAttribute('aria-label') ?? element.textContent) === name,
	);

describe('CatalogueItems', () => {
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

	it.each([
		['kanji', TYPES.kanji, 'Kanji', kanjiIndex, kanjiRows[0].character],
		['words', TYPES.words, 'Words', wordIndex, wordRows[0].word],
		['radicals', TYPES.radicals, 'Radicals', radicalIndex, radicalRows[0].radical],
		['sentences', TYPES.sentences, 'Sentences', sentenceIndex, sentenceRows[0].content],
	] as const)('renders a %s catalogue as its dictionary table', async (_family, type, label, client, text) => {
		vi.mocked(kanjiIndex).mockResolvedValue(page(kanjiRows.slice(0, 1)));
		vi.mocked(wordIndex).mockResolvedValue(page(wordRows.slice(0, 1)));
		vi.mocked(radicalIndex).mockResolvedValue(page(radicalRows.slice(0, 1)));
		vi.mocked(sentenceIndex).mockResolvedValue(page(sentenceRows.slice(0, 1)));

		const view = await renderItems({ catalogueType: type });

		await vi.waitFor(() => expect(view.container.textContent).toContain(text));
		expect(view.container.querySelector(`table[aria-label="${label}"]`)).not.toBeNull();
		expect(client).toHaveBeenCalledWith(
			expect.objectContaining({ catalogue_uuid: UUID, per_page: 25, page: 1 }),
			undefined,
			expect.anything(),
		);
		await view.unmount();
	});

	it('folds a known-kanji catalogue into the kanji table', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(page(kanjiRows.slice(0, 1)));

		const view = await renderItems({ catalogueType: 2 });

		await vi.waitFor(() => expect(view.container.querySelector('table[aria-label="Kanji"]')).not.toBeNull());
		await view.unmount();
	});

	it('pages with Show more', async () => {
		vi.mocked(kanjiIndex)
			.mockResolvedValueOnce(page(kanjiRows.slice(0, 1), true, 2))
			.mockResolvedValueOnce({
				...page(kanjiRows.slice(1, 2)),
				pagination: { ...page([]).pagination, page: 2, total: 2 },
			});

		const view = await renderItems({ catalogueType: TYPES.kanji });

		await vi.waitFor(() => expect(button(view.container, 'Show more kanji')).toBeDefined());
		await view.flush(() => button(view.container, 'Show more kanji')?.click());
		await vi.waitFor(() => expect(view.container.textContent).toContain(kanjiRows[1].character));
		expect(kanjiIndex).toHaveBeenLastCalledWith(expect.objectContaining({ page: 2 }), undefined, expect.anything());
		await view.unmount();
	});

	it('renders an article catalogue from the payload as a linked list', async () => {
		const view = await renderItems({ catalogueType: TYPES.articles, payloadItems: [articleItem] });
		const link = view.container.querySelector(`a[href="/articles/${articleItem.uuid}"]`);

		expect(link?.textContent).toBe('日本語の記事');
		expect(link?.getAttribute('lang')).toBe('ja');
		expect(view.container.textContent).toContain('grammar');
		expect(view.container.textContent).toContain('1 like · 12 views');
		await view.unmount();
	});

	it('says an empty catalogue is empty, with a hint for its owner only', async () => {
		const guest = await renderItems({ catalogueType: TYPES.articles });
		expect(guest.container.textContent).toContain('This catalogue has no items yet.');
		expect(guest.container.textContent).not.toContain('Save to a catalogue');
		await guest.unmount();

		const owner = await renderItems({ catalogueType: TYPES.articles, isOwner: true });
		expect(owner.container.textContent).toContain('Use Save to a catalogue on any article page');
		await owner.unmount();
	});

	it('shows a user-written message when the items cannot be loaded', async () => {
		vi.mocked(wordIndex).mockRejectedValue(new Error('SQLSTATE[08006]'));

		const view = await renderItems({ catalogueType: TYPES.words });

		await vi.waitFor(() =>
			expect(view.container.textContent).toContain(
				'The items could not be loaded. Reload the page to try again.',
			),
		);
		expect(view.container.textContent).not.toContain('SQLSTATE');
		await view.unmount();
	});

	it('offers Manage items to the owner only', async () => {
		const guest = await renderItems({ catalogueType: TYPES.articles, payloadItems: [articleItem] });
		expect(button(guest.container, 'Manage items')).toBeUndefined();
		await guest.unmount();

		const owner = await renderItems({ catalogueType: TYPES.articles, payloadItems: [articleItem], isOwner: true });
		expect(button(owner.container, 'Manage items')?.getAttribute('aria-pressed')).toBe('false');
		expect(button(owner.container, `Remove ${articleItem.title_jp} from this catalogue`)).toBeUndefined();
		await owner.unmount();
	});

	it('adds a Remove column while managing and removes after confirmation', async () => {
		vi.mocked(kanjiIndex).mockResolvedValue(page(kanjiRows.slice(0, 1)));
		vi.mocked(catalogueRemoveItem).mockResolvedValue(204);
		const character = kanjiRows[0].character;

		const view = await renderItems({ catalogueType: TYPES.kanji, isOwner: true });

		await vi.waitFor(() => expect(view.container.textContent).toContain(character));
		await view.flush(() => button(view.container, 'Manage items')?.click());

		const remove = button(view.container, `Remove ${character} from this catalogue`);
		expect(remove).toBeDefined();
		expect(button(view.container, 'Done managing')?.getAttribute('aria-pressed')).toBe('true');

		await view.flush(() => remove?.click());
		expect(document.body.textContent).toContain(`This removes ${character} from the catalogue.`);
		expect(catalogueRemoveItem).not.toHaveBeenCalled();

		await view.flush(() => button(document.body, 'Yes, remove')?.click());
		await vi.waitFor(() => expect(catalogueRemoveItem).toHaveBeenCalledWith(UUID, kanjiRows[0].id));
		await view.unmount();
	});
});
