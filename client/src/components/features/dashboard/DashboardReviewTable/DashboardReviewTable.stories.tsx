import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { reviewQueue } from '../dashboardReviewFixtures';
import { DashboardReviewTable } from './';

const meta = {
	title: 'Features/Dashboard/DashboardReviewTable',
	component: DashboardReviewTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		articles: reviewQueue,
		empty: { title: 'Nothing awaits review' },
	},
} satisfies Meta<typeof DashboardReviewTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Pending and under-review rows, an unbroken 255-character title, and ten long tags. */
export const Queue: Story = {
	play: async ({ canvasElement }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Articles awaiting review' }));

		await expect(table.getByRole('link', { name: '審査待ちの記事' })).toHaveAttribute(
			'href',
			`/articles/${reviewQueue[0].uuid}`,
		);
		await expect(table.getByText('Reviewing')).toBeVisible();
		await expect(table.getByText('No tags')).toBeInTheDocument();
	},
};

export const Loading: Story = { args: { articles: [], loading: true } };

export const Empty: Story = { args: { articles: [] } };

export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
