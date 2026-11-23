import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import SentencesList from './index';

const fetchNextPageMock = vi.fn();
const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});

vi.mock('@/api/sentences/hooks/useInfiniteSentences', () => ({ useInfiniteSentences: vi.fn() }));

let isAuthenticated = false;
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated }) }));

const useInfiniteSentencesMock = vi.mocked(useInfiniteSentences);
const loadedState = {
	sentences: [
		{ id: 1, uuid: 'sentence-uuid', user_id: null, tatoeba_entry: '1001', content: '私は学生です。' },
		{ id: 2, uuid: 'user-sentence-uuid', user_id: 7, tatoeba_entry: null, content: 'はい。' },
	],
	total: 2,
	isPending: false,
	isError: false,
	refetch: vi.fn(),
	isFetchingNextPage: false,
	hasNextPage: true,
	fetchNextPage: fetchNextPageMock,
	error: null,
} as unknown as ReturnType<typeof useInfiniteSentences>;

describe('SentencesList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		isAuthenticated = false;
		searchParams = new URLSearchParams();
		useInfiniteSentencesMock.mockReturnValue(loadedState);
	});

	it('derives URL filters and uses UUID detail links without a no-op action', () => {
		searchParams = new URLSearchParams('keyword=student');
		const html = renderToStaticMarkup(<SentencesList />);

		expect(useInfiniteSentencesMock).toHaveBeenCalledWith({
			filters: { keyword: 'student', per_page: 25 },
		});
		expect(html).toMatch(/<th role="rowheader" scope="row"[^>]*><a href="\/sentence\/sentence-uuid"/);
		expect(html).not.toContain('Add to List');
	});

	it('links Tatoeba sentences with the same URL format and marks user sentences', () => {
		const html = renderToStaticMarkup(<SentencesList />);

		expect(html).toContain('href="https://tatoeba.org/eng/sentences/show/1001"');
		expect(html).toContain('Tatoeba #1001');
		expect(html).toContain('Added by a user');
		expect(html).not.toContain('UserAuthor');
	});

	it('renders PageHeader and FilterBar in place of the legacy search bar', () => {
		const html = renderToStaticMarkup(<SentencesList />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<p[^>]*>Showing 2 of 2<\/p>/);
		expect(html).toContain('role="search" aria-label="Sentence filters"');
		expect(html).toContain('Load more');
	});

	it('offers the create route only to authenticated viewers, as the page action', () => {
		expect(renderToStaticMarkup(<SentencesList />)).not.toContain('/sentences/new');

		isAuthenticated = true;
		expect(renderToStaticMarkup(<SentencesList />)).toMatch(
			/<a[^>]*href="\/sentences\/new"[^>]*>Create sentence<\/a>/,
		);
	});

	it('renders loading and failure states distinctly', () => {
		useInfiniteSentencesMock.mockReturnValueOnce({
			...loadedState,
			sentences: [],
			isPending: true,
		} as ReturnType<typeof useInfiniteSentences>);
		expect(renderToStaticMarkup(<SentencesList />)).toMatch(
			/<table role="table" aria-label="Sentences" aria-busy="true"/,
		);

		useInfiniteSentencesMock.mockReturnValueOnce({
			...loadedState,
			sentences: [],
			isError: true,
		} as ReturnType<typeof useInfiniteSentences>);
		expect(renderToStaticMarkup(<SentencesList />)).toContain('could not be loaded');
	});

	it('names the keyword in the empty search panel', () => {
		searchParams = new URLSearchParams('keyword=はんらん');
		useInfiniteSentencesMock.mockReturnValue({ ...loadedState, sentences: [], total: 0 } as ReturnType<
			typeof useInfiniteSentences
		>);

		expect(renderToStaticMarkup(<SentencesList />)).toContain(
			'No sentences match “<span lang="ja">はんらん</span>”',
		);
	});
});
