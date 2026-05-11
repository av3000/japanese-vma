import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { CatalogueCardSkeleton } from './CatalogueCardSkeleton';

describe('CatalogueCardSkeleton', () => {
	it('renders visible placeholder structure for the catalogue card shape', () => {
		const html = renderToStaticMarkup(<CatalogueCardSkeleton />);
		const statPlaceholderCount = html.match(/data-testid="catalogue-card-skeleton-stat"/g)?.length ?? 0;

		expect(html).toContain('data-testid="catalogue-card-skeleton"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-image"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-date"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-title"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-chip"');
		expect(statPlaceholderCount).toBe(5);
	});
});
