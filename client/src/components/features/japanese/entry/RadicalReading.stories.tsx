import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { radicalGlyphParts, radicalReadingParts } from '../entryValues';
import { radicalTree, radicalWater } from '../related/dictionaryDetail.fixtures';
import { RadicalReading } from './RadicalReading';

const meta = {
	title: 'Features/Japanese/Entry/RadicalReading',
	component: RadicalReading,
	parameters: { layout: 'padded' },
	args: { reading: radicalReadingParts(radicalTree.hiragana), variants: [] },
} satisfies Meta<typeof RadicalReading>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 木: the name in kana with its romaji, and no variants. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'Reading' })).toBeVisible();
		await expect(canvas.getByText('き')).toHaveAttribute('lang', 'ja');
		await expect(canvas.getByText('ki')).toBeVisible();
		await expect(canvas.queryByText('Variants')).not.toBeInTheDocument();
	},
};

/** 水: the forms it takes inside other kanji, 氵 and 氺. */
export const WithVariants: Story = {
	args: {
		reading: radicalReadingParts(radicalWater.hiragana),
		variants: radicalGlyphParts(radicalWater.radical).variants,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Variants')).toBeVisible();
		await expect(canvas.getByText('氵')).toBeVisible();
		await expect(canvas.getByText('氺')).toBeVisible();
	},
};

/** A name in kana without romaji. */
export const KanaOnly: Story = {
	args: { reading: { kana: 'ぼう', romaji: null } },
};

/** No name recorded, but variants are: the variants still show. */
export const VariantsOnly: Story = {
	args: { reading: null, variants: ['氵', '氺'] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.queryByText('Name')).not.toBeInTheDocument();
		await expect(canvas.getByText('Variants')).toBeVisible();
	},
};

/** Neither: one line says so. */
export const Empty: Story = {
	args: { reading: null, variants: [] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No reading is recorded for this radical.')).toBeVisible();
	},
};

export const Mobile: Story = {
	args: WithVariants.args,
	globals: { viewport: { value: 'xs' } },
};
