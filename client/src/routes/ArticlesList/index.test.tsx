import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import ArticleList from './index';

let queryState: Record<string, unknown>;

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: () => queryState,
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('@/components/features/SearchBar', () => ({
	default: () => <div>Article search</div>,
}));

vi.mock('@/components/shared/ArticleCard', () => ({
	default: ({ article }: { article: { title: string } }) => <article>{article.title}</article>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: React.ReactNode }) => <button>{children}</button>,
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

describe('ArticleList', () => {
	beforeEach(() => {
		queryState = {
			articles: [],
			total: 0,
			error: null,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: true,
			isError: false,
		};
	});

	it('uses the article list skeleton inside accessible pending semantics', () => {
		const html = renderToStaticMarkup(<ArticleList />);

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

		const html = renderToStaticMarkup(<ArticleList />);

		expect(html).toContain('Showing 1 of 1');
		expect(html).toContain('Cached article');
		expect(html).not.toContain('data-loading-family');
		expect(html).not.toContain('articles-list-skeleton');
	});
});
