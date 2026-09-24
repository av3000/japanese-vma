import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { PhotoBand, PHOTO_CREDIT } from './';

const meta = {
	title: 'Features/Homepage/PhotoBand',
	component: PhotoBand,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
} satisfies Meta<typeof PhotoBand>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: PHOTO_CREDIT.label })).toHaveAttribute('href', PHOTO_CREDIT.href);
		// Decorative: the image is hidden from assistive technology.
		await expect(canvasElement.querySelector('img')).toHaveAttribute('alt', '');
	},
};

/** 96px tall below 768px; the sides are trimmed and the summit stays centred. */
export const MobileViewport: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
