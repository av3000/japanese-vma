import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { KanjiResource, SentenceResource } from '@/api/generated/model';
import { KanjiTable } from '@/components/features/japanese/Kanji/KanjiTable';
import { SentenceTable } from '@/components/features/japanese/sentence/SentenceTable';
import type { DataTableColumn } from '@/components/shared/DataTable';
import { kanjiRows, sentenceRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { withTrailingAreas, withTrailingColumns } from './trailingColumns';

const removeColumn = <Row extends { uuid: string }>(): DataTableColumn<Row> => ({
	id: 'remove',
	header: 'Remove',
	headerHidden: true,
	width: 'shrink',
	cell: (row) => <button type="button">{`Remove ${row.uuid}`}</button>,
});

const empty = { title: 'Nothing here' };

describe('withTrailingColumns', () => {
	it('appends after the table columns and leaves them alone when there are none', () => {
		const own = [{ id: 'a', header: 'A', cell: () => null }];
		const extra = [{ id: 'b', header: 'B', cell: () => null }];

		expect(withTrailingColumns(own, extra).map((column) => column.id)).toEqual(['a', 'b']);
		expect(withTrailingColumns(own, undefined)).toBe(own);
		expect(withTrailingColumns(own, [])).toBe(own);
	});
});

describe('withTrailingAreas', () => {
	it('gives each trailing column an auto track on every stacked line', () => {
		const layout = { columns: '48px minmax(0, 1fr)', areas: ['glyph meaning', 'glyph reading'] };
		const extra = [
			{ id: 'save', header: 'Save', cell: () => null },
			{ id: 'remove', header: 'Remove', cell: () => null },
		];

		expect(withTrailingAreas(layout, extra)).toEqual({
			columns: '48px minmax(0, 1fr) auto auto',
			areas: ['glyph meaning save remove', 'glyph reading save remove'],
		});
		expect(withTrailingAreas(layout, undefined)).toBe(layout);
		expect(withTrailingAreas(undefined, extra)).toBeUndefined();
	});
});

describe('trailingColumns on the dictionary tables', () => {
	it('renders the trailing column last, after Save', () => {
		const html = renderToStaticMarkup(
			<MemoryRouter>
				<KanjiTable
					kanjis={kanjiRows.slice(0, 1)}
					showSave={false}
					empty={empty}
					trailingColumns={[removeColumn<KanjiResource>()]}
				/>
			</MemoryRouter>,
		);
		const firstRow = html.split('<tbody')[1] ?? '';

		expect(firstRow).toContain(`Remove ${kanjiRows[0].uuid}`);
		expect(firstRow.lastIndexOf('<td')).toBeLessThan(firstRow.indexOf('Remove '));
		expect(firstRow.indexOf('Remove ')).toBeGreaterThan(firstRow.lastIndexOf('</a>'));
	});

	it('leaves a table without trailing columns as it was', () => {
		const html = renderToStaticMarkup(
			<MemoryRouter>
				<SentenceTable sentences={sentenceRows.slice(0, 1)} empty={empty} />
			</MemoryRouter>,
		);
		const withRemove = renderToStaticMarkup(
			<MemoryRouter>
				<SentenceTable
					sentences={sentenceRows.slice(0, 1)}
					empty={empty}
					trailingColumns={[removeColumn<SentenceResource>()]}
				/>
			</MemoryRouter>,
		);

		expect(html).not.toContain('Remove ');
		expect(withRemove).toContain(`Remove ${sentenceRows[0].uuid}`);
	});
});
