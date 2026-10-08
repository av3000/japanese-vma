import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { Pagination, pageItems } from './';

describe('pageItems', () => {
	it('shows every page for short runs', () => {
		expect(pageItems(1, 1)).toEqual([1]);
		expect(pageItems(4, 7)).toEqual([1, 2, 3, 4, 5, 6, 7]);
	});

	it('keeps the first, last and neighbouring pages with gaps between', () => {
		expect(pageItems(1, 12)).toEqual([1, 2, 'gap', 12]);
		expect(pageItems(6, 12)).toEqual([1, 'gap', 5, 6, 7, 'gap', 12]);
		expect(pageItems(12, 12)).toEqual([1, 'gap', 11, 12]);
	});

	it('shows the page instead of a gap that would hide just one', () => {
		expect(pageItems(4, 12)).toEqual([1, 2, 3, 4, 5, 'gap', 12]);
		expect(pageItems(9, 12)).toEqual([1, 'gap', 8, 9, 10, 11, 12]);
	});

	it('clamps a page outside the range', () => {
		expect(pageItems(40, 12)).toEqual([1, 'gap', 11, 12]);
	});
});

describe('Pagination', () => {
	const render = (page: number, pageCount: number) =>
		renderToStaticMarkup(
			<Pagination page={page} pageCount={pageCount} onPageChange={() => {}} label="Kanji pages" />,
		);

	it('renders nothing for a single page', () => {
		expect(render(1, 1)).toBe('');
	});

	it('is a named navigation that marks the current page', () => {
		const html = render(3, 9);

		expect(html).toMatch(/^<nav[^>]*aria-label="Kanji pages"/);
		expect(html).toMatch(/aria-label="Page 3"[^>]*aria-current="page"|aria-current="page"[^>]*aria-label="Page 3"/);
		expect(html.match(/aria-current="page"/g)).toHaveLength(1);
	});

	it('disables Previous on the first page and Next on the last', () => {
		expect(render(1, 9)).toMatch(
			/<button[^>]*disabled[^>]*aria-label="Previous page"|aria-label="Previous page"[^>]*disabled/,
		);
		expect(render(9, 9)).toMatch(
			/<button[^>]*disabled[^>]*aria-label="Next page"|aria-label="Next page"[^>]*disabled/,
		);
	});

	it('hides the gap marker from assistive technology', () => {
		expect(render(6, 12)).toContain('aria-hidden="true">…</li>');
	});
});
