import { renderToStaticMarkup, renderToString } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { createLazyRoute, RouteLoadingBoundary } from './createLazyRoute';

describe('createLazyRoute', () => {
	it('renders a non-empty family fallback while the module is unresolved', () => {
		const PendingPage = createLazyRoute(() => new Promise<never>(() => undefined), { family: 'list' });

		const html = renderToString(<PendingPage />);

		expect(html).toContain('Loading page.');
		expect(html).toContain('data-loading-family="list"');
	});

	it('renders a custom route visual inside the pending region', () => {
		const PendingPage = createLazyRoute(() => new Promise<never>(() => undefined), {
			family: 'detail',
			visual: <div data-testid="detail-fallback" aria-hidden="true" />,
		});

		const html = renderToString(<PendingPage />);

		expect(html).toContain('data-testid="detail-fallback"');
		expect(html).toContain('aria-busy="true"');
	});

	it('renders a resolved child instead of the fallback', () => {
		const html = renderToStaticMarkup(
			<RouteLoadingBoundary family="detail">
				<h1>Resolved page</h1>
			</RouteLoadingBoundary>,
		);

		expect(html).toContain('Resolved page');
		expect(html).not.toContain('Loading page.');
	});
});
