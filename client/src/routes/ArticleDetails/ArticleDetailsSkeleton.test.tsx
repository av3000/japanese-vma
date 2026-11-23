import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

describe('ArticleDetailsSkeleton', () => {
	it('renders an article-detail-shaped skeleton with metadata and comments placeholders', () => {
		const html = renderToStaticMarkup(<ArticleDetailsSkeleton />);
		const paragraphCount = html.match(/data-testid="article-details-skeleton-paragraph"/g)?.length ?? 0;
		const chipCount = html.match(/data-testid="article-details-skeleton-chip"/g)?.length ?? 0;

		expect(html).toContain('data-testid="article-details-skeleton"');
		expect(html).toContain('data-testid="article-details-skeleton-cover"');
		expect(html).toContain('data-testid="article-details-skeleton-author"');
		expect(html).toContain('data-testid="article-details-skeleton-comments"');
		expect(paragraphCount).toBe(4);
		expect(chipCount).toBe(3);
	});
});
