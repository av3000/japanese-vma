import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import PageNotFound from './index';

const render = () =>
	renderToStaticMarkup(
		<MemoryRouter initialEntries={['/no/such/page']}>
			<PageNotFound />
		</MemoryRouter>,
	);

describe('PageNotFound', () => {
	it('renders one h1 that says the page does not exist, with the status code beside it', () => {
		const html = render();

		expect(html.match(/<h1[\s>]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>This page doesn(&#x27;|&apos;|')t exist<\/h1>/);
		expect(html).toContain('>404<');
		expect(html).not.toContain('sucks');
	});

	it('offers a way home and a way into the content', () => {
		const html = render();

		expect(html).toMatch(/<a[^>]*href="\/"[^>]*>[^<]*Back to home/);
		expect(html).toMatch(/<a[^>]*href="\/articles"[^>]*>[^<]*Browse articles/);
	});

	it('marks the Japanese as Japanese and hides only the decorative glyph', () => {
		const html = render();

		expect(html).toMatch(/<span[^>]*lang="ja"[^>]*aria-hidden="true"[^>]*>迷<\/span>/);
		expect(html).toMatch(/<span[^>]*lang="ja"[^>]*>迷子<\/span>/);
		expect(html).toMatch(/<span[^>]*lang="ja"[^>]*>まいご<\/span>/);
		expect(html).not.toMatch(/aria-hidden="true"[^>]*>迷子/);
	});
});
