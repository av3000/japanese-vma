// @vitest-environment jsdom
import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { parsePostListFilters, useInfinitePosts } from '@/api/posts/reads';
import { communityPosts } from '@/components/features/community/fixtures';
import { choose, click, controlLabelled, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import PostsList, { emptyState, listMeta } from './index';

const fetchNextPageMock = vi.fn();
const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to, ...rest }: { children: ReactNode; to: string }) => (
			<a href={to} {...rest}>
				{children}
			</a>
		),
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});

vi.mock('@/api/posts/reads', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/reads')>('@/api/posts/reads');

	return { ...actual, useInfinitePosts: vi.fn() };
});

let isAuthenticated = false;
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated }) }));

const useInfinitePostsMock = vi.mocked(useInfinitePosts);

const loadedState = {
	posts: [communityPosts.default, communityPosts.locked],
	total: 14,
	error: null,
	isPending: false,
	isError: false,
	isFetching: false,
	isFetchingNextPage: false,
	hasNextPage: false,
	fetchNextPage: fetchNextPageMock,
} as unknown as ReturnType<typeof useInfinitePosts>;

const withState = (overrides: Record<string, unknown>) =>
	({ ...loadedState, ...overrides }) as unknown as ReturnType<typeof useInfinitePosts>;

const render = () => renderToStaticMarkup(<PostsList />);

const lastWrittenParams = () => String(setSearchParamsMock.mock.calls.at(-1)?.[0]);

describe('PostsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		isAuthenticated = false;
		searchParams = new URLSearchParams();
		useInfinitePostsMock.mockReturnValue(loadedState);
	});

	it('derives its query filters from the URL', () => {
		searchParams = new URLSearchParams('keyword=kanji&topic=3&sort=popular&hashtag=grammar');

		render();

		expect(useInfinitePostsMock).toHaveBeenCalledWith({
			filters: { per_page: 10, sort: 'popular', keyword: 'kanji', hashtag: 'grammar', topic: 3 },
		});
	});

	it('renders each post as a card linked by UUID', () => {
		const html = render();

		expect(html).toContain('href="/community/post-41"');
		expect(html).toContain('href="/community/post-42"');
		expect(html.match(/<article/g)).toHaveLength(2);
		expect(html).toContain('>Locked</span>');
	});

	it('says how much is shown, and which search and tag produced it', () => {
		expect(render()).toContain('Showing 2 of 14');

		searchParams = new URLSearchParams('keyword=kanji&hashtag=grammar');
		expect(render()).toContain('Showing 2 of 14 · Results for: kanji · Tagged #grammar');
	});

	it('applies topic and sort on change, keeping the other filters', async () => {
		searchParams = new URLSearchParams('keyword=kanji&hashtag=grammar');
		const { container, unmount } = await renderWithAct(<PostsList />);

		choose(controlLabelled<HTMLSelectElement>(container, 'Filter by topic'), '5');
		expect(lastWrittenParams()).toBe('keyword=kanji&hashtag=grammar&topic=5');

		choose(controlLabelled<HTMLSelectElement>(container, 'Sort posts'), 'popular');
		expect(lastWrittenParams()).toBe('keyword=kanji&hashtag=grammar&sort=popular');

		await unmount();
	});

	it('applies the typed search on submit, trimmed', async () => {
		const { container, unmount } = await renderWithAct(<PostsList />);

		typeInto(controlLabelled<HTMLInputElement>(container, 'Search posts'), '  particles ');
		submitForm(requireElement<HTMLFormElement>(container, 'form[role="search"]'));
		expect(lastWrittenParams()).toBe('keyword=particles');

		await unmount();
	});

	it('offers Reset only while a filter is applied, and Reset clears the hashtag too', async () => {
		expect(render()).not.toContain('>Reset<');

		searchParams = new URLSearchParams('hashtag=grammar');
		const { container, unmount } = await renderWithAct(<PostsList />);
		const reset = Array.from(container.querySelectorAll('button')).find((button) => button.textContent === 'Reset');
		if (!reset) throw new Error('Expected a Reset button.');
		click(reset);
		expect(lastWrittenParams()).toBe('');

		await unmount();
	});

	it('shows the list loader only when the first page has nothing to display', () => {
		useInfinitePostsMock.mockReturnValueOnce(withState({ isPending: true, posts: [], total: 0 }));
		expect(render()).toContain('data-loading-family="list"');

		// A filter transition keeps the previous page on screen instead of blanking out.
		useInfinitePostsMock.mockReturnValueOnce(withState({ isPending: true, isFetching: true }));
		const transitioning = render();
		expect(transitioning).not.toContain('data-loading-family="list"');
		expect(transitioning).toContain('Site maintenance this weekend');
		expect(transitioning).toContain('Refreshing results…');
	});

	it('separates the error and load-more states', () => {
		useInfinitePostsMock.mockReturnValueOnce(
			withState({ posts: [], total: 0, isError: true, error: new Error('boom') }),
		);
		const failed = render();
		expect(failed).toContain('Posts couldn&#x27;t be loaded.');
		expect(failed).not.toContain('boom');

		expect(render()).toContain('No more results');

		useInfinitePostsMock.mockReturnValueOnce(withState({ hasNextPage: true }));
		expect(render()).toContain('Load More');

		useInfinitePostsMock.mockReturnValueOnce(withState({ hasNextPage: true, isFetchingNextPage: true }));
		expect(render()).toContain('alt="Loading more..."');
	});

	it('shows the empty state without a "No more results" line under it', () => {
		useInfinitePostsMock.mockReturnValueOnce(withState({ posts: [], total: 0 }));
		const empty = render();

		expect(empty).toContain('data-testid="library-empty-state"');
		expect(empty).toContain('No posts yet');
		expect(empty).not.toContain('No more results');
	});

	it('offers the create route only to authenticated viewers', () => {
		expect(render()).not.toContain('/newpost');

		isAuthenticated = true;
		expect(render()).toMatch(/<a[^>]*href="\/newpost"[^>]*>.*New post.*<\/a>/);
	});

	it('titles the page with its only h1', () => {
		const html = render();

		expect(html.match(/<h1[\s>]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>Community<\/h1>/);
	});
});

describe('emptyState', () => {
	const filters = (query: string) => parsePostListFilters(new URLSearchParams(query));

	it('quotes the search, then falls back to the filters, then to no posts at all', () => {
		expect(emptyState(filters('keyword=kanji'))).toMatchObject({ title: 'No posts match', term: 'kanji' });
		expect(emptyState(filters('topic=5')).title).toBe('No posts match these filters');
		expect(emptyState(filters('hashtag=grammar')).title).toBe('No posts match these filters');
		expect(emptyState(filters('')).title).toBe('No posts yet');
	});
});

describe('listMeta', () => {
	it('joins only the parts that apply', () => {
		expect(listMeta(parsePostListFilters(new URLSearchParams('')), 10, 42)).toBe('Showing 10 of 42');
		expect(listMeta(parsePostListFilters(new URLSearchParams('hashtag=%23news')), 1, 1)).toBe(
			'Showing 1 of 1 · Tagged #news',
		);
	});
});
