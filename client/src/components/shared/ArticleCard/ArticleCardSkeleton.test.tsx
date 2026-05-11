import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ArticleCardSkeleton } from './ArticleCardSkeleton';

describe('ArticleCardSkeleton', () => {
	it('renders visible placeholder structure for the article card shape', () => {
		const html = renderToStaticMarkup(<ArticleCardSkeleton />);
		const pillCount = html.match(/data-testid="article-card-skeleton-pill"/g)?.length ?? 0;
		const levelCount = html.match(/data-testid="article-card-skeleton-level"/g)?.length ?? 0;
		const statCount = html.match(/data-testid="article-card-skeleton-stat"/g)?.length ?? 0;

		expect(html).toContain('data-testid="article-card-skeleton"');
		expect(html).toContain('data-testid="article-card-skeleton-image"');
		expect(html).toContain('data-testid="article-card-skeleton-date"');
		expect(html).toContain('data-testid="article-card-skeleton-title"');
		expect(pillCount).toBe(2);
		expect(levelCount).toBe(6);
		expect(statCount).toBe(3);
	});
});
