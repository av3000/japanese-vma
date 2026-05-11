import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { useInfiniteArticles } from '@/api/articles/hooks/useInfiniteArticles';
import ExploreArticleList from './ExploreArticleList';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: vi.fn(),
}));

describe('ExploreArticleList', () => {
	it('renders the section header and four skeleton cards while articles are pending', () => {
		vi.mocked(useInfiniteArticles).mockReturnValue({
			articles: [],
			total: 0,
			error: null,
			isPending: true,
			isError: false,
		} as unknown as ReturnType<typeof useInfiniteArticles>);

		const html = renderToStaticMarkup(<ExploreArticleList />);
		const skeletonCount = html.match(/data-testid="article-card-skeleton"/g)?.length ?? 0;

		expect(html).toContain('Latest Articles');
		expect(skeletonCount).toBe(4);
		expect(html).not.toContain('spinner.gif');
		expect(html).not.toContain('Loading...');
	});
});
