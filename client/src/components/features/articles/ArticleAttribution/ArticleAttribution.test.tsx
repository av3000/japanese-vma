import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ArticleAttribution } from './';

const nhk = { key: 'nhk-news', name: 'NHK News', homepage_url: 'https://news.web.nhk/newsweb' };

describe('ArticleAttribution', () => {
	it('credits the publisher and links to the full article in a new tab', () => {
		const html = renderToStaticMarkup(
			<ArticleAttribution source={nhk} sourceLink="https://news.web.nhk/newsweb/na/nd-1" />,
		);

		expect(html).toContain('This is an excerpt from <strong>NHK News</strong>');
		expect(html).toContain('href="https://news.web.nhk/newsweb/na/nd-1"');
		expect(html).toContain('target="_blank"');
		expect(html).toContain('rel="noopener noreferrer"');
		expect(html).toContain('Read the full article on NHK News');
	});
});
