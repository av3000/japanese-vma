import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { everyStateArticles, hostileArticles, manyArticles } from '../dashboardFixtures';
import { DashboardArticlesTable } from './';

const meta = {
	title: 'Features/Dashboard/DashboardArticlesTable',
	component: DashboardArticlesTable,
	parameters: { layout: 'padded' },
	args: {
		articles: [...hostileArticles, ...everyStateArticles],
		empty: { title: 'You have no articles yet', hint: 'Articles you write appear here.' },
		onDelete: fn(),
	},
} satisfies Meta<typeof DashboardArticlesTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * Every approval × processing state, a 255-character unbroken title, an article with zero of
 * everything, and a private rejected one.
 */
export const EveryState: Story = {
	play: async ({ canvasElement, args }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Your articles' }));

		await expect(table.getAllByText('Rejected').length).toBeGreaterThan(0);
		await expect(table.getAllByText('Edit and resubmit').length).toBeGreaterThan(0);
		await expect(table.getAllByText('Failed').length).toBeGreaterThan(0);
		await expect(table.getAllByText('Private').length).toBeGreaterThan(0);
		await expect(table.getAllByText('No level yet').length).toBeGreaterThan(0);

		await userEvent.click(table.getByRole('button', { name: 'Delete 却下された非公開の記事' }));
		await expect(args.onDelete).toHaveBeenCalledTimes(1);
		await expect(table.getByRole('link', { name: 'Edit 却下された非公開の記事' })).toHaveAttribute(
			'href',
			expect.stringMatching(/\/edit$/),
		);
	},
};

export const Loading: Story = { args: { articles: [], loading: true } };

export const Empty: Story = { args: { articles: [] } };

/** 500 rows: the sticky header holds and nothing overflows. */
export const FiveHundredRows: Story = {
	args: { articles: manyArticles(500) },
	// Axe over 500 rows outruns the test timeout; the same rows are checked at normal size above.
	parameters: { a11y: { test: 'off' } },
};

/** Stacked rows below 768px. */
export const Mobile: Story = { globals: { viewport: { value: 'xs' } } };
