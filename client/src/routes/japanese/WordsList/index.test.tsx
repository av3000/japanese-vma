import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { WordResource } from '@/api/generated/model';
import { useInfiniteWords } from '@/api/words/hooks/useInfiniteWords';
import WordsList from './index';

const setSearchParamsMock = vi.fn();
const setQueryDataMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
let searchParams = new URLSearchParams();
let isAuthenticated = true;

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
	return { ...actual, useQueryClient: () => ({ setQueryData: setQueryDataMock }) };
});
vi.mock('@/api/words/hooks/useInfiniteWords', async () => {
	const actual = await vi.importActual<typeof import('@/api/words/hooks/useInfiniteWords')>(
		'@/api/words/hooks/useInfiniteWords',
	);
	return { ...actual, useInfiniteWords: vi.fn() };
});
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated }) }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <span>Bookmark</span>;
	},
}));

const useInfiniteWordsMock = vi.mocked(useInfiniteWords);

const word = (fields: Partial<WordResource> = {}): WordResource => ({
	id: 42,
	uuid: 'word-uuid',
	word: '水',
	furigana: 'みず',
	jlpt: 'N5',
	meaning: 'water',
	meanings: ['water'],
	word_types: ['noun (common) (futsuumeishi)'],
	writing_elements: [],
	reading_elements: [],
	word_type: 'noun (common) (futsuumeishi)|',
	word_k_ele: '[]',
	furigana_r_ele: '[]',
	sense: null,
	viewer_catalogue_state: { is_saved: false, is_known: false },
	...fields,
});

const queryResult = (overrides: Record<string, unknown> = {}) =>
	({
		words: [word()],
		total: 1,
		error: null,
		fetchNextPage: vi.fn(),
		hasNextPage: false,
		isFetchingNextPage: false,
		isPending: false,
		isError: false,
		...overrides,
	}) as unknown as ReturnType<typeof useInfiniteWords>;

describe('WordsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
		searchParams = new URLSearchParams();
		isAuthenticated = true;
		useInfiniteWordsMock.mockReturnValue(queryResult());
	});

	it('derives URL filters and separates navigation from catalogue identifiers', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<WordsList />);

		expect(useInfiniteWordsMock).toHaveBeenCalledWith({
			filters: { keyword: 'water', per_page: 25, include: 'viewer_catalogue_state' },
		});
		expect(html).toContain('/word/word-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 42,
			compact: true,
			itemLabel: '水',
			modalTitle: 'Choose Word List to add',
		});
	});

	it('renders PageHeader and FilterBar in place of the legacy search bar', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<WordsList />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>Words<\/h1>/);
		expect(html).toMatch(/<p[^>]*>Showing 1 of 1 · keyword: water<\/p>/);
		expect(html).toContain('role="search" aria-label="Word filters"');
		expect(html).not.toContain('Japanese Keyword:');
	});

	it('renders the word as the row header and the only detail link', () => {
		const html = renderToStaticMarkup(<WordsList />);

		expect(html).toMatch(/<th role="rowheader" scope="row"[^>]*><a href="\/word\/word-uuid"/);
		expect(html.match(/href="\/word\//g)).toHaveLength(1);
	});

	it('shows the clean word types and labelled dashes for missing values', () => {
		useInfiniteWordsMock.mockReturnValue(
			queryResult({
				words: [
					word({
						word: 'ヽ',
						furigana: '-',
						jlpt: '-',
						word_types: ['unclassified'],
						word_type: 'unclassified|',
					}),
				],
			}),
		);

		const html = renderToStaticMarkup(<WordsList />);

		expect(html).toContain('>unclassified<');
		expect(html).not.toContain('unclassified|');
		expect(html).toContain('>No reading</span>');
		expect(html).toContain('>No JLPT level</span>');
	});

	it('leaves the Save column out for guests', () => {
		isAuthenticated = false;

		renderToStaticMarkup(<WordsList />);

		expect(authorizedWidgetProps).toHaveLength(0);
	});

	it('shows skeleton rows while the first page loads', () => {
		useInfiniteWordsMock.mockReturnValue(queryResult({ words: [], total: 0, isPending: true }));

		const html = renderToStaticMarkup(<WordsList />);

		expect(html).toMatch(/<table role="table" aria-label="Words" aria-busy="true"/);
		expect(html).not.toContain('Showing');
		expect(html).not.toContain('data-loading-family');
	});

	it('names the keyword in the empty search panel', () => {
		searchParams = new URLSearchParams('keyword=はんらんする');
		useInfiniteWordsMock.mockReturnValue(queryResult({ words: [], total: 0 }));

		const html = renderToStaticMarkup(<WordsList />);

		expect(html).toContain('No words match “<span lang="ja">はんらんする</span>”');
		expect(html).toMatch(/<p[^>]*>0 results · keyword: はんらんする<\/p>/);
	});

	it('keeps the error alert', () => {
		useInfiniteWordsMock.mockReturnValue(queryResult({ words: [], isError: true, error: new Error('boom') }));

		expect(renderToStaticMarkup(<WordsList />)).toContain('Error: boom');
	});

	it('writes a bookmark change into the list cache', () => {
		renderToStaticMarkup(<WordsList />);

		const onStateChange = authorizedWidgetProps[0].onStateChange as (state: {
			isBookmarked: boolean;
			isKnown: boolean;
		}) => void;
		onStateChange({ isBookmarked: true, isKnown: false });

		expect(setQueryDataMock).toHaveBeenCalledTimes(1);
	});
});
