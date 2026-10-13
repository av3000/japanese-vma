import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { kanjiRadicals } from '../entryValues';
import { kanjiManyMeanings, kanjiPerson } from '../related/dictionaryDetail.fixtures';
import { RadicalLinks } from './RadicalLinks';

const meta = {
	title: 'Features/Japanese/Entry/RadicalLinks',
	component: RadicalLinks,
	parameters: { layout: 'padded' },
	args: { radicals: kanjiRadicals(kanjiPerson.radicals) },
} satisfies Meta<typeof RadicalLinks>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 人 has one radical: its glyph and Kangxi number, linking to the radical's page. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: /No\. 9/ })).toHaveAttribute('href', '/radical/9');
	},
};

/** 為 has two radicals, set side by side. */
export const TwoRadicals: Story = {
	args: { radicals: kanjiRadicals(kanjiManyMeanings.radicals) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('link')).toHaveLength(2);
	},
};

/** Hostile: many radicals wrap over several lines. */
export const ManyRadicals: Story = {
	args: { radicals: kanjiRadicals(['9', '17', '40', '72', '75', '86', '149', '154', '167', '191']) },
};

/** A kanji with no radical numbers renders nothing. */
export const None: Story = {
	args: { radicals: [] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('link')).not.toBeInTheDocument();
	},
};

export const Mobile: Story = {
	args: ManyRadicals.args,
	globals: { viewport: { value: 'xs' } },
};
