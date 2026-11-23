import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { CatalogueResource } from '@/api/generated/model';
import { makeCatalogue } from '@/components/features/Homepage/fixtures';
import { CatalogueCard } from './';

const render = (catalogue: CatalogueResource) =>
	renderToStaticMarkup(
		<MemoryRouter>
			<CatalogueCard catalogue={catalogue} />
		</MemoryRouter>,
	);

const levels = (overrides: Partial<NonNullable<CatalogueResource['jlpt_levels']>> = {}) => ({
	n1: 0,
	n2: 0,
	n3: 0,
	n4: 0,
	n5: 0,
	uncommon: 0,
	...overrides,
});

describe('CatalogueCard', () => {
	it('is one link to the catalogue, named by its title', () => {
		const html = render(makeCatalogue({ uuid: 'cat-1', title: 'N3 kanji for news reading' }));

		expect(html.match(/<a /g)).toHaveLength(1);
		const link = html.match(/<h2[^>]*>(<a [^>]*>)(.*?)<\/a><\/h2>/);
		expect(link?.[1]).toContain('href="/catalogues/cat-1"');
		expect(link?.[2]).toBe('N3 kanji for news reading');
	});

	it('marks a Japanese title as Japanese, and only then', () => {
		expect(render(makeCatalogue({ title: '台所の言葉' }))).toMatch(/<a [^>]*lang="ja"/);
		expect(render(makeCatalogue({ title: 'Cooking verbs' }))).not.toMatch(/<a [^>]*lang="ja"/);
		// English that quotes a particle is still English.
		expect(render(makeCatalogue({ title: 'The は versus が collection' }))).not.toMatch(/<a [^>]*lang="ja"/);
		expect(render(makeCatalogue({ title: '「台所」の言葉 for cooks' }))).toMatch(/<a [^>]*lang="ja"/);
	});

	it('puts the type glyph, type and item count on the cover', () => {
		const html = render(makeCatalogue({ type: 7, type_label: 'Words', items_count: 1203 }));

		expect(html).toMatch(/aria-hidden="true">語<\/span>/);
		expect(html).toContain('>Words</span>');
		expect(html).toContain('>1,203 items</span>');
	});

	it('says "1 item" and "0 items"', () => {
		expect(render(makeCatalogue({ items_count: 1 }))).toContain('>1 item</span>');
		expect(render(makeCatalogue({ items_count: 0 }))).toContain('>0 items</span>');
	});

	it('shows the description and the owner', () => {
		const html = render(
			makeCatalogue({ description: 'Kitchen vocabulary', owner: { id: 1, uuid: 'u', name: 'Kenji' } }),
		);

		expect(html).toContain('>Kitchen vocabulary</p>');
		expect(html).toContain('>by Kenji</span>');
	});

	it('names a Kanji catalogue bar in kanji and a Words catalogue bar in words', () => {
		expect(render(makeCatalogue({ type: 6, jlpt_levels: levels({ n3: 9, n5: 4 }) }))).toContain(
			'aria-label="Mostly N3: N5 4, N3 9"',
		);
		expect(render(makeCatalogue({ type: 8, jlpt_levels: levels({ n3: 9, n5: 4 }) }))).toContain(
			'aria-label="Mostly N3 words: N5 4, N3 9"',
		);
	});

	it('shows no bar without levels, for an empty catalogue, or when every count is uncommon', () => {
		expect(render(makeCatalogue({ jlpt_levels: null }))).not.toContain('role="img"');
		expect(render(makeCatalogue({ jlpt_levels: levels() }))).not.toContain('role="img"');
		expect(render(makeCatalogue({ type: 7, jlpt_levels: levels({ uncommon: 64 }) }))).not.toContain('role="img"');
	});

	it('reads views, comments, likes and downloads, and counts missing engagement as 0', () => {
		const html = render(
			makeCatalogue({
				engagement: { views_count: '12408', comments_count: '96', likes_count: '870', downloads_count: '412' },
			}),
		);

		expect(html.match(/data-stat="(\w+)"/g)).toEqual([
			'data-stat="views"',
			'data-stat="comments"',
			'data-stat="likes"',
			'data-stat="downloads"',
		]);
		expect(html).toMatch(/12,408<span[^>]*> views<\/span>/);
		expect(html).toMatch(/412<span[^>]*> downloads<\/span>/);
		expect(render(makeCatalogue({ engagement: null })).match(/>0<span/g)).toHaveLength(4);
	});
});
