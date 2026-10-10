import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it, vi } from 'vitest';
import { useArticleQuery } from '@/api/articles/details';
import ArticleDetails from './index';

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

	const httpError = (status: number) =>
		new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
			status,
			statusText: '',
			headers: {},
			config: { headers: new AxiosHeaders() },
			data: { title: 'Article not found', detail: `Article article-uuid not found` },
		});

	const renderFailure = (error: unknown) => {
		vi.mocked(useArticleQuery).mockReturnValue({
			data: undefined,
			isLoading: false,
			isError: true,
			error,
		} as never);

		return renderToStaticMarkup(
			<MemoryRouter>
				<ArticleDetails />
			</MemoryRouter>,
		);
	};

	it.each([
		[404, "This article doesn't exist. It may have been deleted."],
		[403, 'This article is private.'],
		[500, "This article couldn't be loaded. Please try again."],
	])('explains a %s in its own words, with a way back', (status, message) => {
		const html = renderFailure(httpError(status));

		expect(html).toContain(`>${message.replace("'", '&#x27;')}</h1>`);
		expect(html).toContain('href="/articles"');
		expect(html).not.toContain('Article not found');
	});

	it('says when the server could not be reached', () => {
		const html = renderFailure(new AxiosError('Network Error', 'ERR_NETWORK'));

		expect(html).toContain('reach the server');
	});
});
