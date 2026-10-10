import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { Initials, initialsOf } from './';

describe('initialsOf', () => {
	it.each([
		['Aki Tanaka', 'AT'],
		['aki', 'A'],
		['Mary Ann de la Cruz', 'MC'],
		['  spaced   out  ', 'SO'],
		['田中太郎', '田'],
		['田中 太郎', '田太'],
		['𠮷野家', '𠮷'],
		['', '?'],
		[null, '?'],
		[undefined, '?'],
	])('%j gives %j', (name, expected) => {
		expect(initialsOf(name)).toBe(expected);
	});
});

describe('Initials', () => {
	it('is hidden from assistive technology', () => {
		const html = renderToStaticMarkup(<Initials name="Aki Tanaka" />);

		expect(html).toContain('aria-hidden="true"');
		expect(html).toContain('>AT</span>');
	});
});
