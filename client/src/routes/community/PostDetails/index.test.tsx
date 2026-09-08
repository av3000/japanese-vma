import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePostQuery } from '@/api/posts/reads';
import PostDetails, { resolveCanonicalPostRedirect } from './index';

const navigateMock = vi.fn();
let routeIdentifier = 'post-uuid';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		useParams: () => ({ post_id: routeIdentifier }),
		useNavigate: () => navigateMock,
	};
});

vi.mock('@/api/posts/reads', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/reads')>('@/api/posts/reads');

	return { ...actual, usePostQuery: vi.fn() };
});

vi.mock('./PostContent', () => ({
	default: ({ post }: { post: { uuid: string } }) => <article>Post content {post.uuid}</article>,
}));

const usePostQueryMock = vi.mocked(usePostQuery);

const loadedPost = { uuid: 'post-uuid', id: 12, title: 'How do I read this kanji?' };

const queryState = (overrides: Record<string, unknown>) =>
	({ data: undefined, isLoading: false, isError: false, ...overrides }) as never;

describe('PostDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		routeIdentifier = 'post-uuid';
		usePostQueryMock.mockReturnValue(queryState({ data: loadedPost }));
	});

	it('renders the detail loader inside accessible pending semantics', () => {
		usePostQueryMock.mockReturnValue(queryState({ isLoading: true }));

		const html = renderToStaticMarkup(<PostDetails />);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('role="status"');
		expect(html).toContain('Loading page.');
		expect(html).toContain('data-loading-family="detail"');
	});

	it('renders the read presentation for a direct UUID visit without redirecting', () => {
		const html = renderToStaticMarkup(<PostDetails />);

		expect(html).toContain('Post content post-uuid');
		expect(usePostQueryMock).toHaveBeenCalledWith('post-uuid');
		expect(navigateMock).not.toHaveBeenCalled();
	});

	it('queries a transitional numeric identifier directly', () => {
		routeIdentifier = '12';

		expect(renderToStaticMarkup(<PostDetails />)).toContain('Post content post-uuid');
		expect(usePostQueryMock).toHaveBeenCalledWith('12');
	});

	it('replaces a numeric URL with the UUID exactly once', () => {
		// These tests render through renderToStaticMarkup, which never runs effects, so the redirect
		// decision is asserted on the pure resolver the effect consumes.
		expect(resolveCanonicalPostRedirect('12', 'post-uuid')).toBe('/community/post-uuid');
		expect(resolveCanonicalPostRedirect('post-uuid', 'post-uuid')).toBeNull();
		expect(resolveCanonicalPostRedirect('12', undefined)).toBeNull();
	});

	it('shows a distinct not-found state', () => {
		usePostQueryMock.mockReturnValue(queryState({ isError: true }));

		const html = renderToStaticMarkup(<PostDetails />);

		expect(html).toContain('Post not found or was deleted.');
		expect(html).not.toContain('data-loading-family');
	});
});
