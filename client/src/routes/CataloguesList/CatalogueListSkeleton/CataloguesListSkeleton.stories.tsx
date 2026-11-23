import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import CataloguesListSkeleton from './CataloguesListSkeleton';

const meta = {
	title: 'Pages/CataloguesList/Skeleton',
	component: CataloguesListSkeleton,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof CataloguesListSkeleton>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What `/catalogues` shows while its chunk loads: four filter controls and twelve card shapes, hidden from assistive technology. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByTestId('catalogues-list-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.getAllByTestId('catalogue-card-skeleton')).toHaveLength(12);
		await expect(canvas.queryByRole('heading')).not.toBeInTheDocument();
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
