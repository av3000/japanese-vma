import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { wordKanaOnly, wordManyMeanings } from '../related/dictionaryDetail.fixtures';
import { EntryMeanings } from './EntryMeanings';

const meta = {
	title: 'Features/Japanese/Entry/EntryMeanings',
	component: EntryMeanings,
	parameters: { layout: 'padded' },
	args: { senses: wordKanaOnly.senses, meanings: wordKanaOnly.meanings },
} satisfies Meta<typeof EntryMeanings>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * どうぞ: three numbered senses. The part of speech shows once because the senses share it, and the
 * third sense's note is muted under its gloss.
 */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const section = canvas.getByRole('region', { name: 'Meanings' });

		const senses = within(section).getAllByRole('listitem');
		await expect(senses).toHaveLength(3);
		// The number markers only render while each sense stays a list item.
		await expect(getComputedStyle(senses[0]).display).toBe('list-item');
		await expect(getComputedStyle(within(section).getByRole('list')).listStyleType).toBe('decimal');
		await expect(within(section).getAllByText('adverb (fukushi)')).toHaveLength(1);
		await expect(canvas.getByText('please; kindly; I beg of you')).toBeVisible();
		await expect(canvas.getByText('previously written as 何卒')).toBeVisible();
		await expect(within(section).getByRole('list')).toHaveAttribute('lang', 'en');
	},
};

/** Hostile: 出る has 20 senses and 77 glosses, the longest entry in the dictionary. */
export const ManySenses: Story = {
	args: { senses: wordManyMeanings.senses, meanings: wordManyMeanings.meanings },
	play: async ({ canvasElement }) => {
		const section = within(canvasElement).getByRole('region', { name: 'Meanings' });

		await expect(within(section).getAllByRole('listitem')).toHaveLength(20);
		await expect(within(section).getByText('to graduate')).toBeVisible();
	},
};

/** No senses in the payload: every gloss in one paragraph, separated by semicolons. */
export const FlatMeanings: Story = {
	args: { senses: [], meanings: ['to leave', 'to exit', 'to go out'] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.queryByRole('list')).toBeNull();
		await expect(canvas.getByText('to leave; to exit; to go out')).toHaveAttribute('lang', 'en');
	},
};

/** Hostile: glosses with no spaces to break on must wrap rather than overflow. */
export const LongGloss: Story = {
	args: {
		senses: [
			{
				parts_of_speech: ['noun (common) (futsuumeishi)'],
				glosses: [
					'a deliberately long gloss that goes on and on to check the wrapping of a single meaning in a narrow column',
					'unbreakable-sequence-of-characters-with-no-spaces-in-it-at-all-to-test-overflow-handling',
				],
				notes: [],
				misc: [],
			},
		],
		meanings: [],
	},
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};

/** Nothing recorded: one line says so. */
export const Empty: Story = {
	args: { senses: [], meanings: [] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No English meaning recorded.')).toBeVisible();
	},
};

export const Mobile: Story = {
	args: { senses: wordManyMeanings.senses, meanings: wordManyMeanings.meanings },
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};
