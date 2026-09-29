import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { makeArticle } from '@/components/features/Homepage/fixtures';
import { ArticleCard } from './';

const render = (article: ArticleResource) =>
	renderToStaticMarkup(
		<MemoryRouter>
			<ArticleCard article={article} />
		</MemoryRouter>,
	);

const noCounts = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 };

describe('ArticleCard', () => {
	it("summarises the article's JLPT levels as one bar with an accessible name", () => {
		const html = render(makeArticle({ jlpt_levels: { n1: 1, n2: 3, n3: 9, n4: 6, n5: 5, uncommon: 1 } }));

		expect(html).toContain('role="img"');
		expect(html).toContain('aria-label="Mostly N3: N5 5, N4 6, N3 9, N2 3, N1 1, uncommon 1"');
	});

	it('no longer renders a row of per-level badges', () => {
		const html = render(makeArticle());

		// The old row labelled each badge "N3: 9"; the bar exposes one label for all of them.
		expect(html).not.toMatch(/aria-label="N\d: \d+"/);
		expect(html.match(/role="img"/g)).toHaveLength(1);
	});

	it('renders no bar while every count is 0', () => {
		expect(render(makeArticle({ jlpt_levels: noCounts }))).not.toContain('role="img"');
	});

	it.each([
		['pending', 'Pending'],
		['processing', 'Processing'],
		['failed', 'Failed'],
	] as const)('shows a %s article with a "%s" status pill', (status, label) => {
		const html = render(
			makeArticle({
				jlpt_levels: noCounts,
				processing_status: { status } as ArticleResource['processing_status'],
			}),
		);

		expect(html).toContain(`>${label}</span>`);
	});

	it.each(['completed', 'superseded'] as const)('shows no status pill for a %s article', (status) => {
		const html = render(makeArticle({ processing_status: { status } as ArticleResource['processing_status'] }));

		expect(html).not.toContain('data-icon=');
	});
});
