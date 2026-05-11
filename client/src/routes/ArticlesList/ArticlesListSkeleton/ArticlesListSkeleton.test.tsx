import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import ArticlesListSkeleton from './ArticlesListSkeleton';

describe('ArticlesListSkeleton', () => {
	it('renders a page-shaped article list skeleton with twelve card placeholders', () => {
		const html = renderToStaticMarkup(<ArticlesListSkeleton />);
		const skeletonCount = html.match(/data-testid="article-card-skeleton"/g)?.length ?? 0;

		expect(html).toContain('data-testid="articles-list-skeleton"');
		expect(skeletonCount).toBe(12);
	});
});
