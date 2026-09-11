import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ArticleList from './index';

let queryState: Record<string, unknown>;
let capturedFilters: Record<string, unknown> | undefined;

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: ({ filters }: { filters?: Record<string, unknown> }) => {
		capturedFilters = filters;

		return queryState;
	},
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('@/components/shared/ArticleCard', () => ({
	default: ({ article }: { article: { title: string } }) => <article>{article.title}</article>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: React.ReactNode }) => <button>{children}</button>,
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const renderAt = (url: string) =>
	renderToStaticMarkup(
		<MemoryRouter initialEntries={[url]}>
			<ArticleList />
		</MemoryRouter>,
	);

describe('ArticleList', () => {
	beforeEach(() => {
		capturedFilters = undefined;
		queryState = {
			articles: [],
			total: 0,
			error: null,
			data: { pages: [] },
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: true,
			isError: false,
		};
	});

	it('uses the article list skeleton inside accessible pending semantics', () => {
		const html = renderAt('/articles');

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('role="status"');
		expect(html).toContain('Loading page.');
		expect(html).toContain('data-loading-family="list"');
		expect(html).toContain('data-testid="articles-list-skeleton"');
	});

	it('keeps populated content visible during a background fetch', () => {
		queryState = {
			...queryState,
			articles: [{ id: 1, uuid: 'article-uuid', title: 'Cached article' }],
			total: 1,
			isPending: false,
			isFetching: true,
		};

		const html = renderAt('/articles');

		expect(html).toContain('Showing 1 of 1');
		expect(html).toContain('Cached article');
		expect(html).not.toContain('data-loading-family');
		expect(html).not.toContain('articles-list-skeleton');
	});

	/**
	 * The regression AFM-06 exists to prevent: the route used to send a numeric
	 * `category` mapped to a nonexistent column, and `views_total` as a sort, which
	 * the backend answers with a 422.
	 */
	it('sends canonical filter parameters read from the URL', () => {
		queryState = { ...queryState, isPending: false };

		renderAt('/articles?q=grammar&jlpt_levels[]=n2&hashtag_ids[]=7&sort=title_jp');

		expect(capturedFilters).toMatchObject({
			q: 'grammar',
			'jlpt_levels[]': ['n2'],
			'hashtag_ids[]': [7],
			sort: 'title_jp',
		});
		expect(capturedFilters).not.toHaveProperty('category');
		expect(capturedFilters).not.toHaveProperty('sort_by');
		expect(capturedFilters).not.toHaveProperty('search');
	});

	it('requests facets because it renders the controls', () => {
		queryState = { ...queryState, isPending: false };

		renderAt('/articles');

		expect(capturedFilters).toMatchObject({ include_facets: true });
	});

	it('never sends page as a filter, since the infinite query owns it', () => {
		queryState = { ...queryState, isPending: false };

		renderAt('/articles?page=3');

		expect(capturedFilters).not.toHaveProperty('page');
	});

	it('ignores URL values the backend would reject', () => {
		queryState = { ...queryState, isPending: false };

		renderAt('/articles?sort=views_total&jlpt_levels[]=n9');

		expect(capturedFilters).toMatchObject({ sort: '-created_at' });
		expect(capturedFilters).not.toHaveProperty('jlpt_levels[]');
	});

	it('renders the search term from the URL', () => {
		queryState = { ...queryState, isPending: false };

		const html = renderAt('/articles?q=grammar');

		expect(html).toContain('Results for: grammar');
	});

	it('renders server facet counts', () => {
		queryState = {
			...queryState,
			isPending: false,
			data: {
				pages: [
					{
						facets: [
							{
								key: 'jlpt_levels',
								label: 'JLPT level',
								type: 'multi',
								values: [{ key: 'n5', label: 'N5', count: 12, selected: false }],
							},
						],
					},
				],
			},
		};

		const html = renderAt('/articles');

		expect(html).toContain('N5 (12)');
	});
});
