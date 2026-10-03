import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { DataTable, type DataTableColumn, type DataTableProps, assertDataTableColumns } from './';

type Row = { id: string; name: string; count: number | null };

const rows: Row[] = [
	{ id: 'a', name: 'Alpha', count: 3 },
	{ id: 'b', name: 'Beta', count: null },
];

const columns: DataTableColumn<Row>[] = [
	{ id: 'name', header: 'Name', rowHeader: true, cell: (row) => <a href={`/rows/${row.id}`}>{row.name}</a> },
	{ id: 'count', header: 'Count', numeric: true, mobileLabel: 'Count', cell: (row) => row.count ?? '—' },
	{ id: 'rank', header: 'Rank', priority: 'low', cell: () => 'n/a' },
	{ id: 'save', header: 'Save', headerHidden: true, cell: (row) => <button type="button">Save {row.name}</button> },
];

const render = (props: Partial<DataTableProps<Row>> = {}) =>
	renderToStaticMarkup(
		<DataTable label="Things" columns={columns} rows={rows} getRowKey={(row) => row.id} {...props} />,
	);

const count = (html: string, pattern: RegExp) => html.match(pattern)?.length ?? 0;

describe('DataTable', () => {
	it('renders a named table with explicit table roles', () => {
		const html = render();

		expect(html).toMatch(/<table role="table" aria-label="Things"/);
		expect(count(html, /role="rowgroup"/g)).toBe(2);
		expect(count(html, /<tr role="row"/g)).toBe(3);
		expect(count(html, /<th role="columnheader" scope="col"/g)).toBe(4);
		expect(count(html, /<td role="cell"/g)).toBe(6);
	});

	it('renders the row-header column as a th with scope row, one per row', () => {
		const html = render();

		expect(count(html, /<th role="rowheader" scope="row"/g)).toBe(2);
		expect(html).toMatch(/<th role="rowheader" scope="row"[^>]*><a href="\/rows\/a">Alpha<\/a><\/th>/);
	});

	it('marks low-priority columns in both the header and the cells', () => {
		const html = render();
		const rankHeader = html.match(/<th role="columnheader" scope="col" class="([^"]*)">Rank<\/th>/);

		expect(rankHeader?.[1]).toMatch(/lowPriority/);
		expect(count(html, /<td role="cell" class="[^"]*lowPriority[^"]*"/g)).toBe(2);
	});

	it('keeps a hidden header available to assistive technology', () => {
		expect(render()).toMatch(/<span class="[^"]*visuallyHidden[^"]*">Save<\/span>/);
	});

	it('renders mobile labels as aria-hidden text and names each cell grid area', () => {
		const html = render();

		expect(html).toMatch(/<span class="[^"]*mobileLabel[^"]*" aria-hidden="true">Count<\/span>3/);
		expect(html).toContain('style="grid-area:count"');
	});

	it('passes a stacked layout through custom properties', () => {
		const html = render({ stacked: { columns: '1fr auto', areas: ['name save', 'count save'] } });

		expect(html).toContain('--data-table-columns:1fr auto');
		expect(html).toContain('--data-table-areas:&quot;name save&quot; &quot;count save&quot;');
	});

	it('renders skeleton rows under the header while loading', () => {
		const html = render({ loading: true, loadingRowCount: 3 });

		expect(html).toContain('aria-busy="true"');
		expect(count(html, /<tr role="row" aria-hidden="true"/g)).toBe(3);
		expect(html).not.toContain('Alpha');
		expect(count(html, /<th role="columnheader"/g)).toBe(4);
	});

	it('replaces the table with a status panel when there are no rows', () => {
		const html = render({ rows: [], empty: { title: 'No things match “x”', hint: 'Try again.' } });

		expect(html).not.toContain('<table');
		expect(html).toMatch(
			/<div class="[^"]*" role="status"><h2[^>]*>No things match “x”<\/h2><p[^>]*>Try again\.<\/p><\/div>/,
		);
	});

	it('keeps an empty table when no empty state is given', () => {
		const html = render({ rows: [] });

		expect(html).toContain('<table');
		expect(count(html, /<tr role="row"/g)).toBe(1);
	});

	it('lets the caller choose the heading level of the empty state', () => {
		const html = render({ rows: [], empty: { title: 'Nothing here', headingLevel: 3 } });

		expect(html).toMatch(/<h3[^>]*>Nothing here<\/h3>/);
		expect(html).not.toContain('<h2');
	});
});

describe('assertDataTableColumns', () => {
	const cell = () => null;

	it('accepts exactly one row header with unique grid-area ids', () => {
		expect(() => assertDataTableColumns(columns)).not.toThrow();
	});

	it('rejects a table without a row header, or with two', () => {
		const withoutRowHeader = columns.map((column) => ({ ...column, rowHeader: false }));
		const twoRowHeaders = columns.map((column) => ({ ...column, rowHeader: true }));

		expect(() => assertDataTableColumns(withoutRowHeader)).toThrow('exactly one rowHeader column, got 0');
		expect(() => assertDataTableColumns(twoRowHeaders)).toThrow('exactly one rowHeader column, got 4');
	});

	it('rejects ids that cannot be grid-area names, and duplicates', () => {
		expect(() => assertDataTableColumns([{ id: 'on yomi', header: 'On', rowHeader: true, cell }])).toThrow(
			'is not a valid grid-area name',
		);
		expect(() =>
			assertDataTableColumns([
				{ id: 'name', header: 'Name', rowHeader: true, cell },
				{ id: 'name', header: 'Again', cell },
			]),
		).toThrow('"name" is used twice');
	});

	it('runs on render in development', () => {
		expect(() => render({ columns: columns.map((column) => ({ ...column, rowHeader: false })) })).toThrow(
			'exactly one rowHeader column',
		);
	});
});
