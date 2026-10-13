import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import ArticlesListSkeleton from './ArticlesListSkeleton';

const meta = {
	title: 'Pages/ArticlesList/Skeleton',
	component: ArticlesListSkeleton,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof ArticlesListSkeleton>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What `/articles` shows while its chunk loads: four filter controls and twelve card shapes, hidden from assistive technology. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByTestId('articles-list-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.getAllByTestId('article-card-skeleton')).toHaveLength(12);
		await expect(canvas.queryByRole('heading')).not.toBeInTheDocument();
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
