// @vitest-environment jsdom
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import type { ArticleResource } from '@/api/generated/model';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { PUBLICITY } from '@/api/publicity';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { makeDashboardArticle, makeProcessing } from '../dashboardFixtures';
import { DashboardArticlesTable } from './';

const EMPTY = { title: 'No articles yet' };

const table = (articles: ArticleResource[], onDelete = vi.fn()) => (
	<MemoryRouter>
		<DashboardArticlesTable articles={articles} empty={EMPTY} onDelete={onDelete} />
	</MemoryRouter>
);

const render = (articles: ArticleResource[]) => renderToStaticMarkup(table(articles));

/** The text of one body row's cells, keyed by grid area. */
const cells = (html: string) => {
	const document = new DOMParser().parseFromString(html, 'text/html');
	const row = document.querySelector('tbody tr');

	return Object.fromEntries(
		Array.from(row?.children ?? [], (cell) => [(cell as HTMLElement).style.gridArea, cell.textContent ?? '']),
	);
};

describe('DashboardArticlesTable', () => {
	it('names the row by its title link and keeps the English title beside it', () => {
		const html = render([makeDashboardArticle({ uuid: 'a1', title_jp: '記事', title_en: 'An article' })]);

		expect(html).toMatch(/<th role="rowheader"[^>]*><a [^>]*href="\/articles\/a1"[^>]*>記事<\/a>/);
		expect(html).toContain('lang="ja"');
		expect(html).toContain('An article');
	});

	it('shows approval, visibility and processing as three separate cells with text', () => {
		const article = makeDashboardArticle({
			uuid: 'a2',
			status: ARTICLE_STATUS.REJECTED,
			publicity: PUBLICITY.PRIVATE,
			processing_status: makeProcessing(ProcessingStatus.failed, 'a2'),
		});
		const row = cells(render([article]));

		expect(row.approval).toBe('ApprovalRejectedEdit and resubmit');
		expect(row.approval).toContain('Edit and resubmit');
		expect(row.visibility).toBe('Private');
		// "Processing" is the stacked-row label, hidden in the table layout.
		expect(row.processing).toBe('ProcessingFailed');
	});

	it('shows no processing pill once processing has finished', () => {
		const html = render([
			makeDashboardArticle({ processing_status: makeProcessing(ProcessingStatus.completed, 'x') }),
		]);
		const row = cells(html);

		expect(row.processing).toBe('Nothing in progress');
		expect(html).not.toContain('Completed');
	});

	it('shows the dominant level, or a labelled dash before processing has run', () => {
		// Cell text includes the stacked-layout label ("Level"), which is aria-hidden.
		expect(cells(render([makeDashboardArticle()])).level).toBe('LevelN5');

		const empty = makeDashboardArticle({
			jlpt_levels: { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 },
			engagement: { stats: null },
		});
		const row = cells(render([empty]));

		expect(row.level).toContain('No level yet');
		expect(row.views).toBe('Views0');
	});

	it('names each action after the article', () => {
		const html = render([makeDashboardArticle({ uuid: 'a3', title_jp: '猫の話' })]);

		expect(html).toContain('href="/articles/a3?edit=1"');
		expect(html).toContain('aria-label="Edit 猫の話"');
		expect(html).toContain('aria-label="Delete 猫の話"');
	});

	it('hands the clicked row to onDelete', async () => {
		const onDelete = vi.fn();
		const article = makeDashboardArticle({ title_jp: '削除する記事' });
		const view = await renderWithAct(table([article], onDelete));

		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, 'button[aria-label="Delete 削除する記事"]').click(),
		);

		expect(onDelete).toHaveBeenCalledWith(article);
		await view.unmount();
	});

	it('renders the empty panel when the owner has no articles', () => {
		const html = render([]);

		expect(html).toContain('role="status"');
		expect(html).toContain('No articles yet');
		expect(html).not.toContain('<table');
	});
});
