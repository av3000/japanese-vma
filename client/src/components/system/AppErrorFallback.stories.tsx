import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import AppErrorFallback from './AppErrorFallback';

/**
 * What `Sentry.ErrorBoundary` renders in `App.tsx` when the app crashes. It takes no props: it shows
 * a fixed message, never the error's own text, and has no reset control. The page is reloaded by hand.
 */
const meta = {
	title: 'System/AppErrorFallback',
	component: AppErrorFallback,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof AppErrorFallback>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('main')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Something went wrong' })).toBeVisible();
		await expect(canvas.getByText('The error has been captured. Refresh the page and try again.')).toBeVisible();
	},
};

/** There is nothing to press: no button or link, only the instruction to refresh. */
export const HasNoControls: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.queryByRole('button')).not.toBeInTheDocument();
		await expect(canvas.queryByRole('link')).not.toBeInTheDocument();
	},
};

export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(
			within(canvasElement).getByRole('heading', { level: 1, name: 'Something went wrong' }),
		).toBeVisible();
	},
};
