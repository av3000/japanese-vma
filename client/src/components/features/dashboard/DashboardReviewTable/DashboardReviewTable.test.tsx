import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import type { ArticleModerationItemResource } from '@/api/generated/model';
import { makeReviewItem } from '../dashboardReviewFixtures';
import { DashboardReviewTable } from './';

const render = (articles: ArticleModerationItemResource[]) =>
	renderToStaticMarkup(
		<MemoryRouter>
			<DashboardReviewTable articles={articles} empty={{ title: 'Nothing awaits review' }} />
		</MemoryRouter>,
	);

describe('DashboardReviewTable', () => {
	it('links each row to the article page by UUID', () => {
		const html = render([
			makeReviewItem({ uuid: 'a51a0f0e-1a4d-4a1e-9b0f-6f6c1c6a2f11', title_jp: '審査待ちの記事' }),
		]);

		expect(html).toContain('aria-label="Articles awaiting review"');
		expect(html).toMatch(
			/<th role="rowheader"[^>]*><a [^>]*href="\/articles\/a51a0f0e-1a4d-4a1e-9b0f-6f6c1c6a2f11"[^>]*>審査待ちの記事<\/a>/,
		);
		expect(html).not.toContain('/article/');
	});

	it('shows the status as a pill with text, and the tags', () => {
		const html = render([makeReviewItem({ status: ARTICLE_STATUS.REVIEWING })]);

		expect(html).toContain('>Reviewing<');
		expect(html).toContain('grammar');
	});

	it('labels a row without tags', () => {
		expect(render([makeReviewItem({ hashtags: [] })])).toContain('No tags');
	});

	it('renders the empty panel when nothing awaits review', () => {
		const html = render([]);

		expect(html).toContain('role="status"');
		expect(html).toContain('Nothing awaits review');
	});
});
