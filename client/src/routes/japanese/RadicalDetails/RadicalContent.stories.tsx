import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { radicalTree, radicalWater } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { radicalKanjiSeeds } from '@/components/features/japanese/related/relatedSeeds';
import RadicalContent from './RadicalContent';

/** Radical detail in the Reading Room layout (UI-DICT-DETAIL, #352), on real dictionary entries. */
const meta = {
	title: 'Pages/RadicalDetails/Content',
	component: RadicalContent,
	parameters: { layout: 'fullscreen', seeds: radicalKanjiSeeds(radicalTree.uuid, 674) },
	args: { radical: { ...radicalTree, kanjis: [] } },
} satisfies Meta<typeof RadicalContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 木 for a guest: 674 kanji, 25 to a page, inline. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const rail = canvas.getByRole('complementary', { name: 'About this radical' });

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(within(rail).getByText('674')).toBeVisible();
		await expect(canvas.getByRole('navigation', { name: 'Kanji pages' })).toBeVisible();
	},
};

export const SignedIn: Story = {
	parameters: { auth: 'member' },
};

/**
 * Signed in at 768px (#550): the kanji table gains its Save column inside the 720px reading column,
 * with real readings such as 杏's キョウ、アン、コウ, and still nothing scrolls sideways.
 */
export const SignedInTablet: Story = {
	parameters: { auth: 'member' },
	globals: { viewport: { value: 'sm' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('キョウ、アン、コウ')).toBeVisible();
		await expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(window.innerWidth);
	},
};

/** 水 with its variants 氵 and 氺. */
export const WithVariants: Story = {
	args: { radical: { ...radicalWater, kanjis: [] } },
	parameters: { seeds: radicalKanjiSeeds(radicalWater.uuid, 657) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1 })).toHaveTextContent('水');
		await expect(canvas.getByText('氵')).toBeVisible();
	},
};
