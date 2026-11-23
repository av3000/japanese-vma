import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { Byline, viewsLabel } from './';

const text = (html: string) => html.replace(/<[^>]+>/g, '');

describe('Byline', () => {
	it('credits a person with initials, the date and the views', () => {
		const html = renderToStaticMarkup(<Byline name="Aki Tanaka" date="2024-05-25T10:00:00+00:00" views={1234} />);

		expect(text(html)).toBe('ATby Aki Tanaka·May 25, 2024·1,234 views');
		expect(html).toContain('<time dateTime="2024-05-25T10:00:00+00:00">');
	});

	it('credits an Imported Article to its source instead of the importing account', () => {
		const html = renderToStaticMarkup(<Byline name="Content Import" source="NHK News" views={3} />);

		expect(text(html)).toBe('from NHK News·3 views');
		expect(html).not.toContain('Content Import');
		expect(html).not.toContain('aria-hidden="true">C');
	});

	it('names an unknown author and leaves out what it was not given', () => {
		const html = renderToStaticMarkup(<Byline name="" />);

		expect(text(html)).toBe('?by Unknown author');
		expect(html).not.toContain('<time');
	});

	it('hides the separators from assistive technology', () => {
		const html = renderToStaticMarkup(<Byline name="Aki" views={0} />);

		expect(html).toContain('aria-hidden="true">·</span>');
	});

	it('counts views in words', () => {
		expect(viewsLabel(0)).toBe('0 views');
		expect(viewsLabel(1)).toBe('1 view');
		expect(viewsLabel(12345678)).toBe('12,345,678 views');
	});
});
