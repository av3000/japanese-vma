import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, within } from '@storybook/test';
import { kanjiRows, repeatRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { KanjiTable } from './';

const meta = {
	title: 'Features/Japanese/KanjiTable',
	component: KanjiTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		kanjis: kanjiRows,
		showSave: true,
		empty: { title: 'No kanji yet' },
		onBookmarkStateChange: fn(),
	},
} satisfies Meta<typeof KanjiTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Signed in: Save on every row, a Known mark on 日. Includes 鬱, 々, 僲 and the 107-character meaning. */
export const SignedIn: Story = {
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Kanji' }));

		await expect(table.getByRole('link', { name: '鬱' })).toHaveAttribute('href', `/kanji/${kanjiRows[4].uuid}`);
		await expect(table.getByRole('button', { name: 'Saved: 日' })).toBeVisible();
		await expect(table.getByRole('button', { name: 'Save 水' })).toBeVisible();
		await expect(table.getByText('Known')).toBeVisible();
		await expect(table.getAllByText('No JLPT level').length).toBeGreaterThan(0);
	},
};

export const Guest: Story = {
	args: { showSave: false },
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Kanji' }));

		await expect(table.queryByRole('columnheader', { name: 'Save' })).not.toBeInTheDocument();
		await expect(table.queryByRole('button', { name: /^Save/ })).not.toBeInTheDocument();
	},
};

export const Loading: Story = { args: { kanjis: [], loading: true } };

export const EmptySearch: Story = {
	args: {
		kanjis: [],
		empty: {
			title: 'No kanji match “はんらんする”',
			hint: 'Try a shorter search, a reading in kana, or clear the filters.',
		},
	},
};

/** 60 rows: the header row sticks while the canvas scrolls. */
export const ManyRows: Story = { args: { kanjis: repeatRows(kanjiRows, 60) } };

/** Stacked rows: glyph | meaning, On, Kun, then Strokes · JLPT · Freq. on one line | Save. */
export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };

export const MobileGuest: Story = { ...Guest, parameters: { viewport: { defaultViewport: 'mobile1' } } };
