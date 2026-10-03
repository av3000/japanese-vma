import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { KanjiResource } from '@/api/generated/model';
import KanjisList from './index';

const fetchNextPageMock = vi.fn();
const setSearchParamsMock = vi.fn();
const setQueryDataMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
let searchParams = new URLSearchParams();
let isAuthenticated = false;
// `satisfies` rather than a cast, so the fixture fails to compile when it drifts from KanjiResource.
const waterKanji = {
	id: 1,
	uuid: 'kanji-uuid',
	character: '水',
	onyomi: ['スイ'],
	kunyomi: ['みず'],
	meanings: ['water', 'river'],
	nanori: [],
	grade: '1',
	stroke_count: 4,
	jlpt: '5',
	frequency: 2,
	radicals: ['水'],
	radical_parts: ['水'],
	viewer_catalogue_state: { is_saved: true, is_known: false },
} satisfies KanjiResource;

type QueryState = {
	kanjis: KanjiResource[];
	total: number;
	isPending: boolean;
	isFetchingNextPage: boolean;
	hasNextPage: boolean;
	error: Error | null;
	isError: boolean;
};

const initialQueryState = (): QueryState => ({
	kanjis: [waterKanji],
	total: 1,
	isPending: false,
	isFetchingNextPage: false,
	hasNextPage: false,
	error: null,
	isError: false,
});

let queryState = initialQueryState();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');

	return {
		...actual,
		useQueryClient: () => ({ setQueryData: setQueryDataMock }),
	};
});

vi.mock('@/api/kanjis/hooks/useInfiniteKanjis', async () => {
	const actual = await vi.importActual<typeof import('@/api/kanjis/hooks/useInfiniteKanjis')>(
		'@/api/kanjis/hooks/useInfiniteKanjis',
	);

	return {
		...actual,
		useInfiniteKanjis: vi.fn(() => ({
			...queryState,
			fetchNextPage: fetchNextPageMock,
		})),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated }),
}));

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <span>{String(props.modalTitle)}</span>;
	},
}));

describe('KanjisList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
		isAuthenticated = false;
		authorizedWidgetProps.length = 0;
		queryState = initialQueryState();
	});

	it('renders kanjis from the v1 query hook', () => {
		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('水');
		expect(html).toContain('water, river');
		expect(html).toContain('/kanji/kanji-uuid');
		expect(html).toContain('Showing 1 of 1');
	});

	it('renders the page heading as the only h1, with the count in its meta line', () => {
		const html = renderToStaticMarkup(<KanjisList />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>Kanji<\/h1>/);
		expect(html).toMatch(/<p[^>]*>Showing 1 of 1<\/p>/);
	});

	it('shows the heading with its title only while the list is loading', () => {
		queryState = { ...queryState, kanjis: [], total: 0, isPending: true };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toMatch(/<h1[^>]*>Kanji<\/h1>/);
		expect(html).not.toContain('Showing');
	});

	it('has no page-level action', () => {
		isAuthenticated = true;

		expect(renderToStaticMarkup(<KanjisList />)).not.toContain('New ');
	});

	it('renders URL-derived filters', () => {
		searchParams = new URLSearchParams('keyword=water&jlpt=5');

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('keyword: water');
		expect(html).toContain('JLPT: N5');
	});

	it('renders the initial loading state', () => {
		queryState = { ...queryState, kanjis: [], total: 0, isPending: true };

		const html = renderToStaticMarkup(<KanjisList />);

		// Skeleton rows inside the table replace PageLoading (UI-DICT-00, #427); the filters render at once.
		expect(html).toMatch(/<table role="table" aria-label="Kanji" aria-busy="true"/);
		expect(html).toContain('role="search"');
		expect(html).not.toContain('data-loading-family');
		expect(html).not.toContain('Load more');
	});

	it('renders the empty state', () => {
		queryState = { ...queryState, kanjis: [], total: 0 };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toMatch(/role="status"><h2[^>]*>No kanji yet<\/h2>/);
		expect(html).not.toContain('<table');
	});

	it('names the keyword in the empty search panel', () => {
		searchParams = new URLSearchParams('keyword=はんらんする');
		queryState = { ...queryState, kanjis: [], total: 0 };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('No kanji match “<span lang="ja">はんらんする</span>”');
		expect(html).toContain('Try a shorter search, a reading in kana, or clear the filters.');
	});

	it('renders the load-more control when another page is available', () => {
		queryState = { ...queryState, hasNextPage: true };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('Load more');
	});

	it('uses Kanji wording in the authenticated catalogue action', () => {
		isAuthenticated = true;

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('Choose Kanji List to add');
		expect(authorizedWidgetProps[0]).toMatchObject({
			initialIsBookmarked: true,
			initialIsKnown: false,
			loadOnMount: false,
			compact: true,
			itemLabel: '水',
			entityId: 1,
		});
	});

	it('renders the glyph as the row header and the only detail link', () => {
		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toMatch(/<th role="rowheader" scope="row"[^>]*><a href="\/kanji\/kanji-uuid">水<\/a><\/th>/);
		expect(html.match(/href="\/kanji\//g)).toHaveLength(1);
	});

	it('leaves the Save column out for guests', () => {
		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).not.toContain('>Save</span></th>');
		expect(authorizedWidgetProps).toHaveLength(0);
	});

	it('shows labelled dashes for the values the API reports as missing', () => {
		queryState = {
			...queryState,
			kanjis: [
				{
					...queryState.kanjis[0],
					character: '僲',
					onyomi: ['-'],
					kunyomi: ['-'],
					meanings: ['-'],
					jlpt: null,
					frequency: 0,
				},
			],
		};

		const html = renderToStaticMarkup(<KanjisList />);

		for (const label of ['No meaning', 'No on’yomi', 'No kun’yomi', 'No JLPT level', 'No frequency rank']) {
			expect(html).toContain(`>${label}</span>`);
		}
	});

	it('writes a bookmark change into the list cache', () => {
		isAuthenticated = true;
		renderToStaticMarkup(<KanjisList />);

		const onStateChange = authorizedWidgetProps[0].onStateChange as (state: {
			isBookmarked: boolean;
			isKnown: boolean;
		}) => void;
		onStateChange({ isBookmarked: false, isKnown: true });

		expect(setQueryDataMock).toHaveBeenCalledTimes(1);
		const update = setQueryDataMock.mock.calls[0][1] as (data: unknown) => { pages: Array<{ items: unknown[] }> };
		const next = update({ pages: [{ items: [{ id: 1, viewer_catalogue_state: null }] }], pageParams: [1] });

		expect(next.pages[0].items[0]).toMatchObject({ viewer_catalogue_state: { is_saved: false, is_known: true } });
	});
});
