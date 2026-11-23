import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { getArticleShowMockHandler } from '@/api/generated/article/article.msw';
import { ARTICLE_UUID, articleDetail } from '../routeFixtures';
import ArticleDetails from './';
import ArticleDetailsSkeleton from './ArticleDetailsSkeleton';

/*
 * The placeholder the article route shows while the article loads. It is sized to stand in for the
 * page, so `BesideLoaded` stacks it above the real page: any spacing drift between the two is a
 * visible jump. They are stacked rather than side by side because both are sized by viewport
 * media queries, so each must have the full viewport width to be a fair comparison.
 */

const meta = {
	title: 'Pages/ArticleDetails/Skeleton',
	component: ArticleDetailsSkeleton,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/articles/:article_id', initialEntries: [`/articles/${ARTICLE_UUID}`] },
	},
} satisfies Meta<typeof ArticleDetailsSkeleton>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Skeleton: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByTestId('article-details-skeleton')).toBeInTheDocument();
		await expect(canvas.queryByRole('heading')).toBeNull();
	},
};

const Beside = () => (
	<>
		<div role="group" aria-label="Skeleton">
			<ArticleDetailsSkeleton />
		</div>
		<hr />
		<div role="group" aria-label="Loaded">
			<ArticleDetails />
		</div>
	</>
);

const showsBoth: Story['play'] = async ({ canvasElement }) => {
	const canvas = within(canvasElement);
	await expect(
		within(canvas.getByRole('group', { name: 'Skeleton' })).getByTestId('article-details-skeleton'),
	).toBeVisible();
	await expect(
		await within(canvas.getByRole('group', { name: 'Loaded' })).findByRole('heading', { level: 1 }),
	).toHaveTextContent('新宿駅の新しい改札が完成');
};

/** The skeleton above the loaded page at 1320px. */
export const BesideLoaded: Story = {
	globals: { viewport: { value: 'lg' } },
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(articleDetail()));
	},
	render: () => <Beside />,
	play: showsBoth,
};

/** The skeleton above the loaded page at 360px. */
export const BesideLoadedMobile: Story = {
	globals: { viewport: { value: 'xs' } },
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(articleDetail()));
	},
	render: () => <Beside />,
	play: showsBoth,
};
