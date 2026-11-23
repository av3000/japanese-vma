import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { PageHeader } from './';

describe('PageHeader', () => {
	it('renders the title as the only h1', () => {
		const html = renderToStaticMarkup(<PageHeader title="Articles" />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>Articles<\/h1>/);
	});

	it('renders no meta line or action wrapper when neither is given', () => {
		const html = renderToStaticMarkup(<PageHeader title="Articles" />);

		expect(html).not.toContain('<p');
		expect(html).not.toContain('<button');
	});

	it('renders the meta line under the title', () => {
		const html = renderToStaticMarkup(<PageHeader title="Articles" meta="Showing 12 of 48" />);

		expect(html).toMatch(/<\/h1>.*<p[^>]*>Showing 12 of 48<\/p>/);
	});

	it('renders the action beside the title', () => {
		const html = renderToStaticMarkup(
			<PageHeader title="Articles" action={<a href="/newarticle">New article</a>} />,
		);

		expect(html).toMatch(/<h1[^>]*>Articles<\/h1><div[^>]*><a href="\/newarticle">New article<\/a><\/div>/);
	});

	it('keeps long Japanese titles as text in the single h1', () => {
		const title = '東京都、来年4月から高校生の通学定期代を全額補助へ　物価高で家計の負担軽減';
		const html = renderToStaticMarkup(<PageHeader title={title} meta="Showing 1 of 1" />);

		expect(html).toContain(`>${title}</h1>`);
		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
	});
});
