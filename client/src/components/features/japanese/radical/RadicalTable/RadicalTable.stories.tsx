import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { radicalRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { RadicalTable } from './';

const meta = {
	title: 'Features/Japanese/RadicalTable',
	component: RadicalTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { radicals: radicalRows, empty: { title: 'No radicals yet' } },
} satisfies Meta<typeof RadicalTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 乙 lists its variant forms, 鬯 has no meaning and no reading, and readings carry romaji. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Radicals' }));

		await expect(table.getAllByRole('rowheader')).toHaveLength(radicalRows.length);
		await expect(table.getByRole('link', { name: '鬯' })).toHaveAttribute(
			'href',
			`/radical/${radicalRows[radicalRows.length - 1].uuid}`,
		);
		await expect(table.getByRole('link', { name: '乙' })).toBeVisible();
		await expect(table.getByText('乛 ⺄ 乚 乙 乀')).toBeVisible();
		await expect(table.getByText('No meaning')).toBeInTheDocument();
		await expect(table.getByText('No reading')).toBeInTheDocument();
	},
};

export const Loading: Story = { args: { radicals: [], loading: true } };

export const EmptySearch: Story = {
	args: {
		radicals: [],
		empty: {
			title: 'No radicals match “みず”',
			hint: 'Try a shorter search, a reading in kana, or clear the filters.',
		},
	},
};

/** Stacked rows: glyph | meaning, reading, strokes. */
export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
