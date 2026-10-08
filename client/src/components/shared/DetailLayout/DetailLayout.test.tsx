/**
 * @vitest-environment jsdom
 */
import { renderToStaticMarkup } from 'react-dom/server';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { DetailLayout } from './';

const slots = {
	header: <h1>Title</h1>,
	main: <p>Body</p>,
	facts: <dl data-slot="facts" />,
	actions: <button type="button">Like</button>,
	extra: <ul data-slot="extra" />,
	after: <section data-slot="after" />,
};

const count = (html: string, needle: string) => html.split(needle).length - 1;

describe('DetailLayout', () => {
	afterEach(() => {
		vi.restoreAllMocks();
	});

	it('renders every slot exactly once', () => {
		const html = renderToStaticMarkup(<DetailLayout {...slots} railLabel="About this article" />);

		expect(count(html, '<h1>Title</h1>')).toBe(1);
		expect(count(html, '<p>Body</p>')).toBe(1);
		expect(count(html, 'data-slot="facts"')).toBe(1);
		expect(count(html, '>Like</button>')).toBe(1);
		expect(count(html, 'data-slot="extra"')).toBe(1);
		expect(count(html, 'data-slot="after"')).toBe(1);
	});

	it('names the rail and keeps facts, actions and extras inside it in that order', () => {
		const html = renderToStaticMarkup(<DetailLayout {...slots} railLabel="About this article" />);
		const rail = html.match(/<aside[^>]*aria-label="About this article"[^>]*>(.*)<\/aside>/)?.[1] ?? '';

		expect(rail.indexOf('data-slot="facts"')).toBeGreaterThanOrEqual(0);
		expect(rail.indexOf('data-slot="facts"')).toBeLessThan(rail.indexOf('>Like</button>'));
		expect(rail.indexOf('>Like</button>')).toBeLessThan(rail.indexOf('data-slot="extra"'));
	});

	it('reads header, main, rail, then after in the DOM', () => {
		const html = renderToStaticMarkup(<DetailLayout {...slots} railLabel="About this article" />);

		expect(html.indexOf('<h1>')).toBeLessThan(html.indexOf('<p>Body'));
		expect(html.indexOf('<p>Body')).toBeLessThan(html.indexOf('<aside'));
		expect(html.indexOf('</aside>')).toBeLessThan(html.indexOf('data-slot="after"'));
	});

	it('renders main alone without an aside when there are no rail slots', () => {
		const html = renderToStaticMarkup(<DetailLayout main={<p>Body</p>} railLabel="About this article" />);

		expect(html).toContain('<p>Body</p>');
		expect(html).not.toContain('<aside');
	});

	it('marks the wide variant for list-like pages and leaves the reading one unmarked', () => {
		const reading = renderToStaticMarkup(<DetailLayout {...slots} railLabel="About this article" />);
		const wide = renderToStaticMarkup(<DetailLayout {...slots} railLabel="About this catalogue" variant="wide" />);
		const rootClass = (html: string) => html.match(/^<div class="([^"]+)"/)?.[1] ?? '';

		expect(rootClass(wide).split(' ')).toHaveLength(rootClass(reading).split(' ').length + 1);
		expect(count(wide, 'data-slot="facts"')).toBe(1);
	});

	it('makes the rail sticky only while it fits the viewport', async () => {
		vi.spyOn(window, 'innerHeight', 'get').mockReturnValue(800);
		const height = vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(400);

		const short = await renderWithAct(<DetailLayout {...slots} railLabel="About this article" />);
		expect(requireElement(short.container, 'aside').getAttribute('data-sticky')).toBe('true');
		await short.unmount();

		height.mockReturnValue(1200);
		const tall = await renderWithAct(<DetailLayout {...slots} railLabel="About this article" />);
		expect(requireElement(tall.container, 'aside').hasAttribute('data-sticky')).toBe(false);
		await tall.unmount();
	});
});
