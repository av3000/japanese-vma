import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { LONGEST_JAPANESE_TITLE, makeArticle } from '@/components/features/Homepage/fixtures';
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

	it('is one link, named by the Japanese title, marked as Japanese', () => {
		const html = render(makeArticle({ uuid: 'abc', title_jp: LONGEST_JAPANESE_TITLE }));

		expect(html.match(/<a /g)).toHaveLength(1);
		const link = html.match(/<h2[^>]*>(<a [^>]*>)(.*?)<\/a><\/h2>/);
		expect(link?.[1]).toContain('href="/articles/abc"');
		expect(link?.[1]).toContain('lang="ja"');
		expect(link?.[2]).toBe(LONGEST_JAPANESE_TITLE);
	});

	it('covers the card with the first kanji of the title and its dominant level', () => {
		const html = render(makeArticle({ title_jp: 'いっしょに歩こう', jlpt_levels: { ...noCounts, n4: 6, n5: 2 } }));

		expect(html).toMatch(/aria-hidden="true">歩<\/span>/);
		expect(html).toMatch(/<span[^>]*aria-hidden="true"[^>]*>N4<\/span>/);
	});

	it('falls back to 記 and drops the badge for a kana-only title with no counts', () => {
		const html = render(makeArticle({ title_jp: 'いっしょにあそぼう', jlpt_levels: noCounts }));

		expect(html).toMatch(/aria-hidden="true">記<\/span>/);
		expect(html).not.toMatch(/>N[1-5]<\/span>/);
	});

	it('shows the English title only when there is one', () => {
		expect(render(makeArticle({ title_en: 'A new station opens' }))).toContain('>A new station opens</p>');
		expect(render(makeArticle({ title_en: undefined }))).not.toContain('</p>');
	});

	it('shows three tags, then how many more', () => {
		const hashtags = ['#a', '#b', '#c', '#d', '#e'].map((content, id) => ({
			id,
			content,
			created_at: null,
			updated_at: null,
		}));
		const html = render(makeArticle({ hashtags }));

		expect(html.match(/title="#[a-e]"/g)).toEqual(['title="#a"', 'title="#b"', 'title="#c"']);
		expect(html).toContain('<span aria-hidden="true">+2</span>');
		expect(html).toContain('2 more</span>');
	});

	it('renders no tag list for an article without tags', () => {
		expect(render(makeArticle({ hashtags: [] }))).not.toContain('aria-label="Tags"');
	});

	it('reads each stat with its name, and counts a missing summary as 0', () => {
		const html = render(
			makeArticle({
				engagement: {
					stats: { views_count: '1284', comments_count: '37', likes_count: '212', downloads_count: '0' },
				} as ArticleResource['engagement'],
			}),
		);

		expect(html).toMatch(/data-stat="views">.*1,284<span[^>]*> views<\/span>/);
		expect(html).toMatch(/data-stat="comments">.*37<span[^>]*> comments<\/span>/);
		expect(html).toMatch(/data-stat="likes">.*212<span[^>]*> likes<\/span>/);
		expect(render(makeArticle({ engagement: { stats: null } })).match(/>0<span/g)).toHaveLength(3);
	});

	it('dates the card without the time', () => {
		// This year's dates drop the year, so the fixture follows the clock.
		const createdAt = `${new Date().getFullYear()}-09-23T12:00:00Z`;
		const html = render(makeArticle({ created_at: createdAt }));

		expect(html).toContain(`dateTime="${createdAt}">09月23日</time>`);
	});
});
