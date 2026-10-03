import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import PageNotFound from './';

const meta = {
	title: 'Routes/NotFound',
	component: PageNotFound,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof PageNotFound>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The 404: muted 迷 behind the heading, a dictionary-style 迷子 entry, two ways onward. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: "This page doesn't exist" })).toBeInTheDocument();
		await expect(canvas.getByRole('link', { name: 'Back to home' })).toHaveAttribute('href', '/');
		await expect(canvas.getByRole('link', { name: 'Browse articles' })).toHaveAttribute('href', '/articles');
		await expect(canvas.queryByText('迷')).not.toBeNull();
	},
};

/** 360px: the glyph shrinks, the buttons wrap under each other if they must. */
export const Mobile: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
