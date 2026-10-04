// @vitest-environment jsdom
import { beforeEach, describe, expect, it } from 'vitest';
import { renderWithAct } from '@/test/renderWithAct';
import { DataTable, type DataTableColumn } from './';

/*
 * Long lists grow with "Load more" and a Save click replaces one row. Only that row should render
 * again: the rest of the table must not do N x M cell work for it (V1-FINDINGS-02, #488).
 */

type Row = { id: string; name: string };

const rows = Array.from({ length: 100 }, (_, index): Row => ({ id: `row-${index}`, name: `Row ${index}` }));

const renders = new Map<string, number>();
const columns: DataTableColumn<Row>[] = [
	{ id: 'name', header: 'Name', rowHeader: true, cell: (row) => row.name },
	{
		id: 'count',
		header: 'Renders',
		cell: (row) => {
			renders.set(row.id, (renders.get(row.id) ?? 0) + 1);

			return row.name.length;
		},
	},
];

const table = (tableRows: Row[], tableColumns = columns) => (
	<DataTable label="Rows" columns={tableColumns} rows={tableRows} getRowKey={(row) => row.id} />
);

/** What a Save click does to the list: the same rows, with one replaced by a changed copy. */
const withRowChanged = (index: number) =>
	rows.map((row, position) => (position === index ? { ...row, name: `${row.name} (changed)` } : row));

describe('DataTable row rendering', () => {
	beforeEach(() => renders.clear());

	it('renders every row once on mount', async () => {
		const view = await renderWithAct(table(rows));

		expect(renders.size).toBe(100);
		expect([...renders.values()].every((count) => count === 1)).toBe(true);
		await view.unmount();
	});

	it('re-renders only the changed row out of 100', async () => {
		const view = await renderWithAct(table(rows));
		renders.clear();

		await view.rerender(table(withRowChanged(7)));

		expect([...renders.entries()]).toEqual([['row-7', 1]]);
		await view.unmount();
	});

	it('skips every row when a parent re-renders with the same rows and columns', async () => {
		const view = await renderWithAct(table(rows));
		renders.clear();

		await view.rerender(table([...rows]));

		expect(renders.size).toBe(0);
		await view.unmount();
	});

	it('re-renders all rows when the columns change, which is why callers memoise them', async () => {
		const view = await renderWithAct(table(rows));
		renders.clear();

		await view.rerender(table(rows, [...columns]));

		expect(renders.size).toBe(100);
		await view.unmount();
	});
});
