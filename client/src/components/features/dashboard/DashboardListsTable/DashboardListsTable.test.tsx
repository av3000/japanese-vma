// @vitest-environment jsdom
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import type { CatalogueResource } from '@/api/generated/model';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { knownLists, makeDashboardCatalogue } from '../dashboardListFixtures';
import { PUBLICITY } from '../dashboardValues';
import { DashboardListsTable } from './';

const EMPTY = { title: 'No lists yet' };

const table = (catalogues: CatalogueResource[], onDelete = vi.fn()) => (
	<MemoryRouter>
		<DashboardListsTable catalogues={catalogues} empty={EMPTY} onDelete={onDelete} />
	</MemoryRouter>
);

const render = (catalogues: CatalogueResource[]) => renderToStaticMarkup(table(catalogues));

const rowCells = (html: string, index = 0) => {
	const document = new DOMParser().parseFromString(html, 'text/html');
	const row = document.querySelectorAll('tbody tr')[index];

	return Object.fromEntries(
		Array.from(row?.children ?? [], (cell) => [(cell as HTMLElement).style.gridArea, cell.textContent ?? '']),
	);
};

describe('DashboardListsTable', () => {
	it('names the row by a link to the list', () => {
		const html = render([makeDashboardCatalogue({ uuid: 'c1', title: 'Verbs' })]);

		expect(html).toMatch(/<th role="rowheader"[^>]*><a [^>]*href="\/catalogues\/c1"[^>]*>Verbs<\/a>/);
	});

	it('shows type, items, visibility and the counts', () => {
		const row = rowCells(
			render([
				makeDashboardCatalogue({
					type: 7,
					type_label: 'Words',
					items_count: 13108,
					publicity: PUBLICITY.PRIVATE,
					engagement: { likes_count: '1', views_count: '2500', downloads_count: '7', comments_count: '0' },
				}),
			]),
		);

		expect(row.type).toBe('Words');
		expect(row.items).toBe('Items13,108');
		expect(row.visibility).toBe('Private');
		expect(row.views).toBe('Views2,500');
		expect(row.downloads).toBe('Downloads7');
	});

	it('tags the Known lists as built in and offers them no actions', () => {
		const html = render(knownLists);

		expect(html.match(/Built-in<\/span>/g)).toHaveLength(4);
		expect(html).not.toContain('aria-label="Edit');
		expect(html).not.toContain('aria-label="Delete');
		expect(rowCells(html).actions).toBe('Built-in lists cannot be edited or deleted');
	});

	it('reads missing engagement as zero', () => {
		const row = rowCells(render([makeDashboardCatalogue({ engagement: null })]));

		expect(row.views).toBe('Views0');
	});

	it('links Edit to the list form and hands Delete the row', async () => {
		const onDelete = vi.fn();
		const catalogue = makeDashboardCatalogue({ uuid: 'c2', title: 'Kanji to review' });
		const html = render([catalogue]);

		expect(html).toContain('href="/catalogues/c2/edit"');
		expect(html).toContain('aria-label="Edit Kanji to review"');

		const view = await renderWithAct(table([catalogue], onDelete));
		await view.flush(() =>
			requireElement<HTMLButtonElement>(view.container, 'button[aria-label="Delete Kanji to review"]').click(),
		);

		expect(onDelete).toHaveBeenCalledWith(catalogue);
		await view.unmount();
	});
});
