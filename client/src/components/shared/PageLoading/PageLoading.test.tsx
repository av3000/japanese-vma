import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import PageLoading, { PAGE_LOADING_FAMILIES } from './PageLoading';

describe('PageLoading', () => {
	for (const family of PAGE_LOADING_FAMILIES) {
		it(`renders the ${family} family as an accessible pending region`, () => {
			const html = renderToStaticMarkup(<PageLoading family={family} />);

			expect(html).toContain('aria-busy="true"');
			expect(html).toContain('role="status"');
			expect(html).toContain('Loading page.');
			expect(html).toContain(`data-loading-family="${family}"`);
			expect(html).toContain('aria-hidden="true"');
		});
	}

	it('renders a route-specific visual inside the same pending semantics', () => {
		const html = renderToStaticMarkup(
			<PageLoading family="detail" visual={<div data-testid="article-detail-visual" aria-hidden="true" />} />,
		);

		expect(html).toContain('data-testid="article-detail-visual"');
		expect(html).toContain('data-loading-family="detail"');
		expect(html).toContain('Loading page.');
	});
});
