import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import Footer from './';

const meta = {
	title: 'Features/Footer',
	component: Footer,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof Footer>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Brand, the Explore and Dictionary columns, the licence text, then the contact line. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const nav = canvas.getByRole('navigation', { name: 'Footer' });
		await expect(within(nav).getByRole('link', { name: 'Kanji' })).toHaveAttribute('href', '/kanjis');
		await expect(canvas.getByRole('link', { name: 'JMdict' })).toBeInTheDocument();
		await expect(canvas.getByRole('link', { name: 'jplearning.online@gmail.com' })).toBeInTheDocument();
	},
};

/** Narrow canvas: the brand stacks above the two link columns. */
export const Mobile: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
