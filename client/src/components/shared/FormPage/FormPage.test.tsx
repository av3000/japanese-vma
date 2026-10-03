import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import { FormCard, FormLayout, FormNote, FormPage } from './';

const inRouter = (node: React.ReactNode) => renderToStaticMarkup(<MemoryRouter>{node}</MemoryRouter>);

describe('FormPage', () => {
	it('renders the title as the only h1 and names the section with it', () => {
		const html = inRouter(
			<FormPage
				title="New article"
				intro="Paste a Japanese text."
				backLink={{ to: '/articles', label: 'Articles' }}
			>
				<form aria-label="Article" />
			</FormPage>,
		);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		const id = html.match(/<h1 id="([^"]+)"/)?.[1];
		expect(id).toBeTruthy();
		expect(html).toMatch(new RegExp(`<section[^>]*aria-labelledby="${id}"`));
		expect(html).toMatch(/<a [^>]*href="\/articles"[^>]*>.*Articles<\/a>/);
		expect(html).toContain('Paste a Japanese text.');
		expect(html.indexOf('href="/articles"')).toBeLessThan(html.indexOf('<h1'));
	});

	it('renders no back link or intro when they are omitted', () => {
		const html = inRouter(
			<FormPage title="New sentence">
				<form aria-label="Sentence" />
			</FormPage>,
		);

		expect(html).not.toContain('<a ');
		expect(html).not.toContain('<p');
	});
});

describe('FormLayout', () => {
	it('orders alert, main column, aside and actions in the DOM', () => {
		const html = renderToStaticMarkup(
			<FormLayout
				alert={<div role="alert">Check the highlighted fields and try again.</div>}
				aside={<FormCard title="Settings">Visibility</FormCard>}
				actions={<button type="submit">Create article</button>}
			>
				<FormCard>Japanese title</FormCard>
			</FormLayout>,
		);

		const order = ['Check the highlighted fields', 'Japanese title', 'Settings', 'Create article'].map((text) =>
			html.indexOf(text),
		);
		expect(order.every((at) => at >= 0)).toBe(true);
		expect([...order].sort((a, b) => a - b)).toEqual(order);
	});

	it('renders no empty wrappers for omitted slots', () => {
		const html = renderToStaticMarkup(
			<FormLayout actions={<button type="submit">Save</button>}>
				<FormCard>Sentence</FormCard>
			</FormLayout>,
		);

		// Layout root, main column and actions; no alert or aside wrappers.
		expect(html.match(/<div[ >]/g)).toHaveLength(3);
		expect(html).not.toContain('role="alert"');
	});
});

describe('FormCard and FormNote', () => {
	it('names a titled card by its h2 and leaves an untitled card unnamed', () => {
		const titled = renderToStaticMarkup(<FormCard title="Settings">Tags</FormCard>);
		const untitled = renderToStaticMarkup(<FormCard>Content</FormCard>);

		const id = titled.match(/<h2 id="([^"]+)"/)?.[1];
		expect(titled).toMatch(new RegExp(`<section[^>]*aria-labelledby="${id}"`));
		expect(titled).toMatch(/<h2[^>]*>Settings<\/h2>/);
		expect(untitled).not.toContain('aria-labelledby');
		expect(untitled).not.toContain('<h2');
	});

	it('names the note by its heading', () => {
		const html = renderToStaticMarkup(<FormNote title="What happens next">We analyse the text.</FormNote>);

		const id = html.match(/<p id="([^"]+)"[^>]*>What happens next<\/p>/)?.[1];
		expect(id).toBeTruthy();
		expect(html).toMatch(new RegExp(`<aside[^>]*aria-labelledby="${id}"`));
		expect(html).toContain('We analyse the text.');
	});
});
