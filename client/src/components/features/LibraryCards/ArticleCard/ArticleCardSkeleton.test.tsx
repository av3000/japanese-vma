import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ArticleCardSkeleton } from './ArticleCardSkeleton';

const count = (html: string, testId: string) => html.match(new RegExp(`data-testid="${testId}"`, 'g'))?.length ?? 0;

describe('ArticleCardSkeleton', () => {
	it('renders the article card anatomy as placeholders, hidden from assistive technology', () => {
		const html = renderToStaticMarkup(<ArticleCardSkeleton />);

		expect(html).toMatch(/<article aria-hidden="true"[^>]*data-testid="article-card-skeleton"/);
		expect(html).toContain('data-testid="article-card-skeleton-cover"');
		expect(html).toContain('data-testid="article-card-skeleton-date"');
		expect(html).toContain('data-testid="article-card-skeleton-title"');
		expect(html).toContain('data-testid="article-card-skeleton-subtitle"');
		expect(count(html, 'article-card-skeleton-pill')).toBe(2);
		expect(count(html, 'article-card-skeleton-jlpt-bar')).toBe(1);
		// Views, comments, likes.
		expect(count(html, 'article-card-skeleton-stat')).toBe(3);
	});
});
