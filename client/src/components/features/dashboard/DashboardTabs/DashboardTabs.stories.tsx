import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { DashboardTabs } from './';

const meta = {
	title: 'Features/Dashboard/DashboardTabs',
	component: DashboardTabs,
	args: { active: 'articles', isAdmin: false },
} satisfies Meta<typeof DashboardTabs>;

export default meta;

type Story = StoryObj<typeof meta>;

export const SignedInUser: Story = {
	play: async ({ canvasElement }) => {
		const nav = within(canvasElement).getByRole('navigation', { name: 'Dashboard sections' });
		const links = within(nav).getAllByRole('link');

		await expect(links.map((link) => link.textContent)).toEqual(['Articles', 'Lists']);
		await expect(within(nav).getByRole('link', { name: 'Articles' })).toHaveAttribute('aria-current', 'page');
		await expect(within(nav).getByRole('link', { name: 'Lists' })).not.toHaveAttribute('aria-current');
	},
};

export const ListsOpen: Story = {
	args: { active: 'lists' },
	play: async ({ canvasElement }) => {
		const nav = within(canvasElement).getByRole('navigation', { name: 'Dashboard sections' });

		await expect(within(nav).getByRole('link', { name: 'Lists' })).toHaveAttribute('aria-current', 'page');
	},
};

export const Admin: Story = {
	args: { isAdmin: true, active: 'review' },
	play: async ({ canvasElement }) => {
		const nav = within(canvasElement).getByRole('navigation', { name: 'Dashboard sections' });

		await expect(within(nav).getByRole('link', { name: 'Review queue' })).toHaveAttribute('aria-current', 'page');
	},
};
