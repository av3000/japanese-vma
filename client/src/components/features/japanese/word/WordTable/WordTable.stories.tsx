import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, within } from '@storybook/test';
import { repeatRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { WordTable } from './';

const meta = {
	title: 'Features/Japanese/WordTable',
	component: WordTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		words: wordRows,
		showSave: true,
		empty: { title: 'No words yet' },
		onBookmarkStateChange: fn(),
	},
} satisfies Meta<typeof WordTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * 掛ける (a 200-character meaning), 特別警報 (no level), ヽ (no reading), the 37-character reading
 * and the six-entry type list.
 */
export const SignedIn: Story = {
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Words' }));

		await expect(table.getByRole('link', { name: '掛ける' })).toHaveAttribute('href', `/word/${wordRows[2].uuid}`);
		await expect(table.getByRole('button', { name: 'Saved: 交流' })).toBeVisible();
		await expect(table.getByRole('button', { name: 'Save 氾濫' })).toBeVisible();
		await expect(table.getAllByText('No reading').length).toBeGreaterThan(0);
	},
};

export const Guest: Story = {
	args: { showSave: false },
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Words' }));

		await expect(table.queryByRole('button', { name: /^Save/ })).not.toBeInTheDocument();
	},
};

export const Loading: Story = { args: { words: [], loading: true } };

export const EmptySearch: Story = {
	args: {
		words: [],
		empty: {
			title: 'No words match “はんらんする”',
			hint: 'Try a shorter search, a reading in kana, or clear the filters.',
		},
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent('No words match “はんらんする”');
	},
};

export const ManyRows: Story = { args: { words: repeatRows(wordRows, 60) } };

/** Stacked rows: word, reading and level on line 1, then the meaning, then the type | Save. */
export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
