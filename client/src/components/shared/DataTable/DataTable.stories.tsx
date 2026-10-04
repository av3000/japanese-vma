import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import type { KanjiResource, RadicalResource, SentenceResource, WordResource } from '@/api/generated/model';
import { DataTable, type DataTableColumn } from './';
import { kanjiRows, radicalRows, repeatRows, sentenceRows, wordRows } from './DataTable.fixtures';

/*
 * Table mechanics on the hostile dictionary rows: plain-text cells, so what is visible here is the
 * table itself (widths, wrapping, stacking, sticky header). The styled dictionary cells live in the
 * list tables under `components/features/japanese`.
 */

const text = (values: string[]) => {
	const present = values.filter((value) => value !== '-');

	return present.length > 0 ? present.slice(0, 3).join(', ') : '—';
};

const kanjiColumns: DataTableColumn<KanjiResource>[] = [
	{
		id: 'glyph',
		header: 'Kanji',
		rowHeader: true,
		width: 'shrink',
		cell: (row) => <span lang="ja">{row.character}</span>,
	},
	{ id: 'meaning', header: 'Meaning', width: 'fill', cell: (row) => text(row.meanings) },
	{ id: 'on', header: 'On’yomi', mobileLabel: 'On', cell: (row) => <span lang="ja">{text(row.onyomi)}</span> },
	{ id: 'kun', header: 'Kun’yomi', mobileLabel: 'Kun', cell: (row) => <span lang="ja">{text(row.kunyomi)}</span> },
	{ id: 'strokes', header: 'Strokes', numeric: true, mobileLabel: 'Strokes', cell: (row) => row.stroke_count },
	{ id: 'jlpt', header: 'JLPT', mobileLabel: 'JLPT', cell: (row) => (row.jlpt ? `N${row.jlpt}` : '—') },
	{
		id: 'freq',
		header: 'Freq.',
		numeric: true,
		priority: 'low',
		mobileLabel: 'Freq.',
		cell: (row) => row.frequency || '—',
	},
	{
		id: 'save',
		header: 'Save',
		headerHidden: true,
		width: 'shrink',
		cell: () => <button type="button">Save</button>,
	},
];

const kanjiStacked = {
	columns: '48px auto auto minmax(0, 1fr) auto',
	areas: [
		'glyph meaning meaning meaning save',
		'glyph on on on save',
		'glyph kun kun kun save',
		'glyph strokes jlpt freq save',
	],
};

const wordColumns: DataTableColumn<WordResource>[] = [
	{ id: 'word', header: 'Word', rowHeader: true, cell: (row) => <span lang="ja">{row.word}</span> },
	{
		id: 'reading',
		header: 'Reading',
		cell: (row) => <span lang="ja">{row.furigana === '-' ? '—' : row.furigana}</span>,
	},
	{ id: 'meaning', header: 'Meaning', width: 'fill', cell: (row) => row.meaning },
	{ id: 'type', header: 'Type', priority: 'low', mobileLabel: 'Type', cell: (row) => row.word_types.join(', ') },
	{ id: 'jlpt', header: 'JLPT', cell: (row) => (row.jlpt && row.jlpt !== '-' ? row.jlpt : '—') },
];

const wordStacked = {
	columns: 'auto auto minmax(0, 1fr)',
	areas: ['word reading jlpt', 'meaning meaning meaning', 'type type type'],
};

const sentenceColumns: DataTableColumn<SentenceResource>[] = [
	{
		id: 'sentence',
		header: 'Sentence',
		rowHeader: true,
		width: 'fill',
		cell: (row) => <span lang="ja">{row.content}</span>,
	},
	{
		id: 'source',
		header: 'Source',
		mobileLabel: 'Source',
		cell: (row) => (row.tatoeba_entry ? `Tatoeba #${row.tatoeba_entry}` : 'Added by a user'),
	},
];

const radicalColumns: DataTableColumn<RadicalResource>[] = [
	{
		id: 'glyph',
		header: 'Radical',
		rowHeader: true,
		width: 'shrink',
		cell: (row) => <span lang="ja">{row.radical ?? '—'}</span>,
	},
	{ id: 'meaning', header: 'Meaning', width: 'fill', cell: (row) => row.meaning ?? '—' },
	{
		id: 'reading',
		header: 'Reading',
		mobileLabel: 'Reading',
		cell: (row) => <span lang="ja">{row.hiragana ?? '—'}</span>,
	},
	{ id: 'strokes', header: 'Strokes', numeric: true, mobileLabel: 'Strokes', cell: (row) => row.strokes ?? '—' },
];

const meta = {
	title: 'Components/DataTable',
	component: DataTable<KanjiResource>,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		label: 'Kanji',
		columns: kanjiColumns,
		rows: kanjiRows,
		getRowKey: (row) => row.uuid,
		stacked: kanjiStacked,
	},
} satisfies Meta<typeof DataTable<KanjiResource>>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 鬱 (29 strokes, four kun readings), 々 and 僲 (nothing to show), and a 107-character meaning. */
export const Kanji: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const table = canvas.getByRole('table', { name: 'Kanji' });

		await expect(within(table).getAllByRole('columnheader')).toHaveLength(kanjiColumns.length);
		await expect(within(table).getAllByRole('rowheader')).toHaveLength(kanjiRows.length);
		await expect(within(table).getByRole('rowheader', { name: '鬱' })).toBeVisible();
		await expect(within(table).getByRole('columnheader', { name: 'Save' })).toBeInTheDocument();
	},
};

/** Plain table shape for the other lists; the stories below reuse it through `render`. */
const renderTable = <Row,>(label: string, columns: DataTableColumn<Row>[], rows: Row[], stacked?: typeof wordStacked) =>
	function TableStory() {
		return (
			<DataTable
				label={label}
				columns={columns}
				rows={rows}
				getRowKey={(row) => JSON.stringify(row)}
				stacked={stacked}
			/>
		);
	};

/** The longest reading, the longest type list and a 200-character meaning. */
export const Words: Story = {
	render: renderTable('Words', wordColumns, wordRows, wordStacked),
	play: async ({ canvasElement }) => {
		const table = within(canvasElement).getByRole('table', { name: 'Words' });

		await expect(within(table).getByRole('rowheader', { name: '掛ける' })).toBeVisible();
		await expect(within(table).getByRole('columnheader', { name: 'Type' })).toBeInTheDocument();
	},
};

/** No stacked layout: one cell per line below 768px. A 70-character sentence and はい。 */
export const Sentences: Story = {
	render: renderTable('Sentences', sentenceColumns, sentenceRows),
};

/** 鬯 has no meaning and no reading. */
export const Radicals: Story = {
	render: renderTable('Radicals', radicalColumns, radicalRows, {
		columns: '48px minmax(0, 1fr)',
		areas: ['glyph meaning', 'glyph reading', 'glyph strokes'],
	}),
};

/** Skeleton rows under the real header; the table is `aria-busy`. */
export const Loading: Story = {
	args: { loading: true },
	play: async ({ canvasElement }) => {
		const table = within(canvasElement).getByRole('table', { name: 'Kanji' });

		await expect(table).toHaveAttribute('aria-busy', 'true');
		await expect(within(table).queryByRole('rowheader', { name: '日' })).not.toBeInTheDocument();
	},
};

export const Empty: Story = {
	args: {
		rows: [],
		empty: {
			title: 'No kanji match “はんらんする”',
			hint: 'Try a shorter search, a reading in kana, or clear the filters.',
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.queryByRole('table')).not.toBeInTheDocument();
		await expect(canvas.getByRole('status')).toHaveTextContent('No kanji match');
	},
};

/** 60 rows: scroll the canvas to check that the header row sticks. */
export const ManyRows: Story = {
	args: { rows: repeatRows(kanjiRows, 60) },
};

export const Mobile: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

export const MobileWords: Story = {
	...Words,
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
