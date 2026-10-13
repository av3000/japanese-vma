import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { wordKanaOnly, wordManyMeanings } from '../related/dictionaryDetail.fixtures';
import { EntryMeanings } from './EntryMeanings';

const meta = {
	title: 'Features/Japanese/Entry/EntryMeanings',
	component: EntryMeanings,
	parameters: { layout: 'padded' },
	args: { meanings: ['to leave', 'to exit', 'to go out'] },
} satisfies Meta<typeof EntryMeanings>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The glosses in one paragraph, separated by semicolons, under a named section. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'Meanings' })).toBeVisible();
		await expect(canvas.getByText('to leave; to exit; to go out')).toHaveAttribute('lang', 'en');
	},
};

/** One gloss: no separator. */
export const SingleMeaning: Story = {
	args: { meanings: ['person'] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('person')).toBeVisible();
	},
};

/** Hostile: 出る has 77 glosses, the longest list in the dictionary. */
export const ManyMeanings: Story = {
	args: { meanings: wordManyMeanings.meanings },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/to leave; to exit; to go out/)).toBeVisible();
	},
};

/** Hostile: glosses with no spaces to break on must wrap rather than overflow. */
export const LongGloss: Story = {
	args: {
		meanings: [
			'a deliberately long gloss that goes on and on to check the wrapping of a single meaning in a narrow column',
			'unbreakable-sequence-of-characters-with-no-spaces-in-it-at-all-to-test-overflow-handling',
		],
	},
};

export const KanaOnlyWord: Story = {
	args: { meanings: wordKanaOnly.meanings },
};

/** Nothing recorded: one line says so. */
export const Empty: Story = {
	args: { meanings: [] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No English meaning recorded.')).toBeVisible();
	},
};

export const Mobile: Story = {
	args: { meanings: wordManyMeanings.meanings },
	globals: { viewport: { value: 'xs' } },
};
