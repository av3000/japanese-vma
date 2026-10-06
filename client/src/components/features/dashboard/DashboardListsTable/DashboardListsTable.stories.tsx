import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, within } from '@storybook/test';
import { hostileCatalogues, knownLists, manyCatalogues } from '../dashboardListFixtures';
import { DashboardListsTable } from './';

const meta = {
	title: 'Features/Dashboard/DashboardListsTable',
	component: DashboardListsTable,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		catalogues: [...hostileCatalogues, ...knownLists],
		empty: { title: 'You have no lists yet' },
		onDelete: fn(),
	},
} satisfies Meta<typeof DashboardListsTable>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Custom lists of every type, a 255-character unbroken title, then the four Known lists. */
export const WithKnownLists: Story = {
	play: async ({ canvasElement, args }) => {
		const table = within(within(canvasElement).getByRole('table', { name: 'Your lists' }));

		await expect(table.getAllByText('Built-in')).toHaveLength(4);
		await expect(table.queryByRole('button', { name: 'Delete Known Kanjis' })).not.toBeInTheDocument();

		await userEvent.click(table.getByRole('button', { name: 'Delete Private verbs' }));
		await expect(args.onDelete).toHaveBeenCalledTimes(1);
	},
};

/** A brand-new user: only the Known lists, all empty, none removable. */
export const NewUser: Story = { args: { catalogues: knownLists } };

export const Loading: Story = { args: { catalogues: [], loading: true } };

export const Empty: Story = { args: { catalogues: [] } };

export const FiveHundredRows: Story = { args: { catalogues: manyCatalogues(500) } };

export const Mobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
