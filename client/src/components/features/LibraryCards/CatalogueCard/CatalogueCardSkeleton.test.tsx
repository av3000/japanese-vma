import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { CatalogueCardSkeleton } from './CatalogueCardSkeleton';

const count = (html: string, testId: string) => html.match(new RegExp(`data-testid="${testId}"`, 'g'))?.length ?? 0;

describe('CatalogueCardSkeleton', () => {
	it('renders the catalogue card anatomy as placeholders, hidden from assistive technology', () => {
		const html = renderToStaticMarkup(<CatalogueCardSkeleton />);

		expect(html).toMatch(/<article aria-hidden="true"[^>]*data-testid="catalogue-card-skeleton"/);
		expect(html).toContain('data-testid="catalogue-card-skeleton-cover"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-title"');
		expect(html).toContain('data-testid="catalogue-card-skeleton-owner"');
		// Catalogue cards carry no date.
		expect(html).not.toContain('data-testid="catalogue-card-skeleton-date"');
		expect(count(html, 'catalogue-card-skeleton-pill')).toBe(2);
		// Views, comments, likes, downloads; the item count moved onto the cover.
		expect(count(html, 'catalogue-card-skeleton-stat')).toBe(4);
	});
});
