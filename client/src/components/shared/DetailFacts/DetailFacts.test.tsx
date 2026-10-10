import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { characterCount, DetailFacts } from './';

describe('characterCount', () => {
	it('counts code points without whitespace', () => {
		expect(characterCount('水を飲む')).toBe(4);
		expect(characterCount('  水を\n\n飲む\r\n ')).toBe(4);
		expect(characterCount('𠮷野家')).toBe(3);
		expect(characterCount('')).toBe(0);
		expect(characterCount(null)).toBe(0);
	});
});

describe('DetailFacts', () => {
	it('renders a titled definition list with formatted counts', () => {
		const html = renderToStaticMarkup(
			<DetailFacts
				title="In this reading"
				facts={[
					{ term: 'Kanji', value: 171 },
					{ term: 'Characters', value: 12345678 },
				]}
			/>,
		);

		expect(html).toMatch(/<h2 id="[^"]+"[^>]*>In this reading<\/h2>/);
		expect(html).toMatch(/<dt[^>]*>Kanji<\/dt><dd[^>]*>171<\/dd>/);
		expect(html).toContain('>12,345,678</dd>');
		expect(html).not.toContain('aria-busy');
	});

	it('reads "Counting…" for a value still being worked out and marks the list busy', () => {
		const html = renderToStaticMarkup(
			<DetailFacts
				title="In this reading"
				facts={[
					{ term: 'Kanji', value: null },
					{ term: 'Characters', value: 0 },
				]}
			/>,
		);

		expect(html).toContain('aria-busy="true"');
		expect(html).toMatch(/<dt[^>]*>Kanji<\/dt><dd[^>]*>Counting…<\/dd>/);
		expect(html).toMatch(/<dt[^>]*>Characters<\/dt><dd[^>]*>0<\/dd>/);
	});

	it('renders the footer only when given', () => {
		const bare = renderToStaticMarkup(<DetailFacts title="In this catalogue" headingLevel={3} facts={[]} />);
		const withFooter = renderToStaticMarkup(
			<DetailFacts title="In this catalogue" facts={[]}>
				<p>bar</p>
			</DetailFacts>,
		);

		expect(bare).toContain('<h3');
		expect(bare).not.toContain('<p>bar</p>');
		expect(withFooter).toContain('<p>bar</p>');
	});
});
