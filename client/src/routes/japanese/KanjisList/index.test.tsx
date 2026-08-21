import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import KanjisList from './index';

const fetchNextPageMock = vi.fn();
const setSearchParamsMock = vi.fn();
const setQueryDataMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
let searchParams = new URLSearchParams();
let isAuthenticated = false;
let queryState = {
	kanjis: [
		{
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
		},
	],
	total: 1,
	isPending: false,
	isFetchingNextPage: false,
	hasNextPage: false,
	error: null as Error | null,
	isError: false,
};

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

vi.mock('@/assets/images/spinner.gif', () => ({
	default: 'spinner.gif',
}));

describe('KanjisList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
		isAuthenticated = false;
		authorizedWidgetProps.length = 0;
		queryState = {
			...queryState,
			kanjis: [
				{
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
				},
			],
			total: 1,
			isPending: false,
			isFetchingNextPage: false,
			hasNextPage: false,
			error: null,
			isError: false,
		};
	});

	it('renders kanjis from the v1 query hook', () => {
		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('水');
		expect(html).toContain('water, river');
		expect(html).toContain('/kanji/kanji-uuid');
		expect(html).toContain('Showing 1 of 1');
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

		expect(html).toContain('alt="Loading..."');
	});

	it('renders the empty state', () => {
		queryState = { ...queryState, kanjis: [], total: 0 };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('No kanjis found.');
	});

	it('renders the load-more control when another page is available', () => {
		queryState = { ...queryState, hasNextPage: true };

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('Load More');
	});

	it('uses Kanji wording in the authenticated catalogue action', () => {
		isAuthenticated = true;

		const html = renderToStaticMarkup(<KanjisList />);

		expect(html).toContain('Choose Kanji List to add');
		expect(authorizedWidgetProps[0]).toMatchObject({
			initialIsBookmarked: true,
			initialIsKnown: false,
			loadOnMount: false,
		});
	});
});
