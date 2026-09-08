import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfinitePosts } from '@/api/posts/reads';
import PostsList from './index';

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

vi.mock('@/api/posts/reads', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/reads')>('@/api/posts/reads');

	return { ...actual, useInfinitePosts: vi.fn() };
});

let isAuthenticated = false;
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated }) }));

const useInfinitePostsMock = vi.mocked(useInfinitePosts);

const loadedState = {
	posts: [
		{
			id: 12,
			uuid: 'post-uuid',
			title: 'How do I read this kanji?',
			topic: 3,
			topic_label: 'FAQ',
			locked: false,
			hashtags: [{ id: 1, content: 'kanji' }],
			authorName: 'Hana',
			formattedDate: '9/1/2026',
			engagementCounts: { likes: 3, views: 17, comments: 2, downloads: 0 },
		},
	],
	total: 1,
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

describe('PostsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		isAuthenticated = false;
		searchParams = new URLSearchParams();
		useInfinitePostsMock.mockReturnValue(loadedState);
	});

	it('derives its query filters from the URL', () => {
		searchParams = new URLSearchParams('keyword=kanji&topic=3&sort=popular');

		renderToStaticMarkup(<PostsList />);

		expect(useInfinitePostsMock).toHaveBeenCalledWith({
			filters: { per_page: 10, sort: 'popular', keyword: 'kanji', topic: 3 },
		});
	});

	it('navigates by UUID and renders contract-mapped engagement counts', () => {
		const html = renderToStaticMarkup(<PostsList />);

		expect(html).toContain('/community/post-uuid');
		expect(html).not.toContain('/community/12');
		// PostItem separates each count from its label with a non-breaking space.
		const text = html.replace(/\u00a0/g, ' ');
		expect(text).toContain('17 Views');
		expect(text).toContain('2 Comments');
		expect(html).toContain('FAQ');
	});

	it('shows the list loader only when the first page has nothing to display', () => {
		useInfinitePostsMock.mockReturnValueOnce(withState({ isPending: true, posts: [], total: 0 }));
		expect(renderToStaticMarkup(<PostsList />)).toContain('data-loading-family="list"');

		// A filter transition keeps the previous page on screen instead of blanking out.
		useInfinitePostsMock.mockReturnValueOnce(withState({ isPending: true, isFetching: true }));
		const transitioning = renderToStaticMarkup(<PostsList />);
		expect(transitioning).not.toContain('data-loading-family="list"');
		expect(transitioning).toContain('How do I read this kanji?');
		expect(transitioning).toContain('Refreshing results...');
	});

	it('separates the empty, error and load-more states', () => {
		useInfinitePostsMock.mockReturnValueOnce(withState({ posts: [], total: 0 }));
		const empty = renderToStaticMarkup(<PostsList />);
		expect(empty).toContain('No posts found.');
		expect(empty).not.toContain('data-loading-family');

		useInfinitePostsMock.mockReturnValueOnce(
			withState({ posts: [], total: 0, isError: true, error: new Error('boom') }),
		);
		expect(renderToStaticMarkup(<PostsList />)).toContain('Posts could not be loaded.');

		expect(renderToStaticMarkup(<PostsList />)).toContain('no more results...');

		useInfinitePostsMock.mockReturnValueOnce(withState({ hasNextPage: true }));
		expect(renderToStaticMarkup(<PostsList />)).toContain('Load More');

		useInfinitePostsMock.mockReturnValueOnce(withState({ hasNextPage: true, isFetchingNextPage: true }));
		expect(renderToStaticMarkup(<PostsList />)).toContain('Loading more...');
	});

	it('offers Clear search only while filters are active', () => {
		expect(renderToStaticMarkup(<PostsList />)).not.toContain('Clear search');

		searchParams = new URLSearchParams('keyword=kanji');
		expect(renderToStaticMarkup(<PostsList />)).toContain('Clear search');
	});

	it('offers the create route only to authenticated viewers', () => {
		expect(renderToStaticMarkup(<PostsList />)).not.toContain('/newpost');

		isAuthenticated = true;
		expect(renderToStaticMarkup(<PostsList />)).toContain('/newpost');
	});
});
