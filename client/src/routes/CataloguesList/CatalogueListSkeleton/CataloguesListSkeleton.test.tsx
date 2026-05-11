import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import CataloguesListSkeleton from './CataloguesListSkeleton';

describe('CataloguesListSkeleton', () => {
	it('renders a page-shaped catalogue list skeleton with twelve card placeholders', () => {
		const html = renderToStaticMarkup(<CataloguesListSkeleton />);
		const skeletonCount = html.match(/data-testid="catalogue-card-skeleton"/g)?.length ?? 0;

		expect(html).toContain('data-testid="catalogues-list-skeleton"');
		expect(skeletonCount).toBe(12);
	});
});
