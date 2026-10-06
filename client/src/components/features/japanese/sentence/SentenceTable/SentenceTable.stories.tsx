import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { sentenceRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { SentenceTable } from './';

const meta = {
	title: 'Features/Japanese/SentenceTable',
	component: SentenceTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { sentences: sentenceRows, empty: { title: 'No sentences yet' } },
} satisfies Meta<typeof SentenceTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A 70-character sentence added by a user, はい。 and 嘘！. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Sentences' }));

		await expect(table.getByRole('link', { name: 'はい。' })).toHaveAttribute(
			'href',
			`/sentence/${sentenceRows[3].uuid}`,
		);
		await expect(table.getByRole('link', { name: 'Tatoeba #2215' })).toHaveAttribute(
			'href',
			'https://tatoeba.org/eng/sentences/show/2215',
		);
		await expect(table.getByText('Added by a user')).toBeVisible();
	},
};

export const Loading: Story = { args: { sentences: [], loading: true } };

export const EmptySearch: Story = {
	args: {
		sentences: [],
		empty: {
			title: 'No sentences match “はんらん”',
			hint: 'Try a shorter search, a reading in kana, or clear the filters.',
		},
	},
};

/** No stacked layout: one cell per line, the sentence full width. */
export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
