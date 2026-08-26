import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import ArticleDetails from './index';
import { useArticleQuery } from '@/api/articles/details';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		useParams: () => ({ article_id: 'article-uuid' }),
	};
});

vi.mock('@/api/articles/details', () => ({
	useArticleQuery: vi.fn(),
}));

vi.mock('./ArticleContent', () => ({
	default: () => <article>Article content</article>,
}));

describe('ArticleDetails', () => {
	it('uses the article detail skeleton inside accessible pending semantics', () => {
		vi.mocked(useArticleQuery).mockReturnValue({
			data: undefined,
			isLoading: true,
			isError: false,
		} as never);

		const html = renderToStaticMarkup(<ArticleDetails />);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('role="status"');
		expect(html).toContain('Loading page.');
		expect(html).toContain('data-loading-family="detail"');
		expect(html).toContain('data-testid="article-details-skeleton"');
	});
});
