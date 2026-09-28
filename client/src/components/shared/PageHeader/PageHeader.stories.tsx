import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, within } from '@storybook/test';
import { Button } from '@/components/shared/Button';
import { PageHeader } from './';

const meta = {
	title: 'Shared/PageHeader',
	component: PageHeader,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { title: 'Articles', meta: 'Showing 12 of 48' },
} satisfies Meta<typeof PageHeader>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'Articles' })).toBeVisible();
		await expect(canvas.getByText('Showing 12 of 48')).toBeVisible();
	},
};

/** While a list is loading the header renders the title alone, with no skeleton. */
export const TitleOnly: Story = {
	args: { meta: undefined },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'Articles' })).toBeVisible();
		await expect(canvas.queryByText(/Showing/)).not.toBeInTheDocument();
	},
};

/** The action is a router-link `Button`, as on the Articles and Catalogues lists. */
export const WithAction: Story = {
	args: {
		action: (
			<Button to="/newarticle" variant="primary">
				New article
			</Button>
		),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('link', { name: 'New article' })).toHaveAttribute('href', '/newarticle');
		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
	},
};

export const WithSearchMeta: Story = {
	args: { meta: 'Showing 3 of 3 · Results for: 文法' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/Results for: 文法/)).toBeVisible();
	},
};

/** Long text wraps inside its column and never pushes the action off screen. */
export const LongTitle: Story = {
	args: {
		title: '東京都、来年4月から高校生の通学定期代を全額補助へ　物価高で家計の負担軽減　対象は約30万人、所得制限は設けず',
		meta: 'Showing 1 of 1 · Results for: 通学定期代を全額補助',
		action: (
			<Button type="button" variant="primary" onClick={fn()}>
				New article
			</Button>
		),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1 })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'New article' })).toBeVisible();
	},
};

/** Below 768px the action wraps under the title. */
export const MobileViewport: Story = {
	args: {
		action: (
			<Button type="button" variant="primary" onClick={fn()}>
				New article
			</Button>
		),
	},
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
