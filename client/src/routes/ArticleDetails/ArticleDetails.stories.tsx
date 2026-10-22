import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getArticleShowMockHandler } from '@/api/generated/article/article.msw';
import { ARTICLE_UUID, articleDetail, failWith } from '../routeFixtures';
import ArticleDetails from './';

/*
 * The article route itself: it fetches by the `:article_id` param through the generated show
 * handler and renders the loading skeleton, the unavailable panel or the article. The reading
 * layout is covered by `Pages/ArticleDetails` (`ArticleContent`); these stories are the states
 * around it.
 */

const meta = {
	title: 'Pages/ArticleDetails/Route states',
	component: ArticleDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/articles/:article_id', initialEntries: [`/articles/${ARTICLE_UUID}`] },
	},
} satisfies Meta<typeof ArticleDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(articleDetail()));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent(
			'新宿駅の新しい改札が完成',
		);
	},
};

/** The article request never answers: the route shows the detail skeleton and announces it. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleShowMockHandler(async () => {
				await delay('infinite');

				return articleDetail();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByRole('status')).toHaveTextContent('Loading page.');
		await expect(canvas.getByTestId('article-details-skeleton')).toBeInTheDocument();
	},
};

/** A 404 says the article does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This article doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Articles' })).toHaveAttribute('href', '/articles');
	},
};

/** A 403: a private article someone else wrote says it is private, not that it is missing. */
export const Private: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(() => failWith(403)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', { level: 1, name: 'This article is private.' }),
		).toBeVisible();
	},
};

/** A 500 asks the reader to try again, in words that blame neither the article nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This article couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
