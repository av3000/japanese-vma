import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getArticleStoreMockHandler } from '@/api/generated/article/article.msw';
import type { ArticleCreatedResource } from '@/api/generated/model/articleCreatedResource';
import type { StoreArticleRequest } from '@/api/generated/model/storeArticleRequest';
import { currentLocation } from '@/storybook/decorators';
import { ARTICLE_UUID, articleDetail, failWith, rejectWith } from '../routeFixtures';
import ArticleCreatePage from './';

/*
 * The New article route on its real mutation: the generated POST handler answers, and the story
 * asserts the body it received and where the route navigated afterwards. The route only has a
 * create form, so there is no entity to load: no loading, not-found or not-owner state exists.
 */

const meta = {
	title: 'Pages/ArticleCreate',
	component: ArticleCreatePage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/newarticle', initialEntries: ['/newarticle'] },
	},
} satisfies Meta<typeof ArticleCreatePage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated POST handler received, newest last; cleared before each story that posts. */
const posted: StoreArticleRequest[] = [];

const created = (): ArticleCreatedResource => ({
	uuid: ARTICLE_UUID,
	processing_status: articleDetail().processing_status as ArticleCreatedResource['processing_status'],
});

const fillRequiredFields = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(canvas.getByLabelText('Japanese title'), '新宿駅の新しい改札が完成');
	await userEvent.type(canvas.getByLabelText('English title'), 'New ticket gates at Shinjuku');
	await userEvent.type(
		canvas.getByLabelText('Japanese text'),
		'ＪＲ新宿駅で、新しい改札が完成しました。東口と西口をつなぐ通路が広くなります。',
	);
	await userEvent.type(canvas.getByLabelText('Source link'), 'https://www3.nhk.or.jp/news/easy/k10014322571000.html');
};

/** Fill the required fields and submit: the body is trimmed and typed, and the route opens the new article. */
export const Create: Story = {
	beforeEach: ({ msw }) => {
		posted.length = 0;

		msw.use(
			getArticleStoreMockHandler(async ({ request }) => {
				posted.push((await request.json()) as StoreArticleRequest);

				return created();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'New article' })).toBeVisible();

		await fillRequiredFields(canvas);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await waitFor(() => expect(currentLocation()).toBe(`/articles/${ARTICLE_UUID}`));
		await expect(posted).toEqual([
			{
				title_jp: '新宿駅の新しい改札が完成',
				title_en: 'New ticket gates at Shinjuku',
				content_jp: 'ＪＲ新宿駅で、新しい改札が完成しました。東口と西口をつなぐ通路が広くなります。',
				content_en: null,
				source_link: 'https://www3.nhk.or.jp/news/easy/k10014322571000.html',
				publicity: true,
				tags: [],
			},
		]);
	},
};

/** The POST never answers: the submit control is disabled, and the route stays where it is. */
export const Submitting: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleStoreMockHandler(async () => {
				await delay('infinite');

				return created();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillRequiredFields(canvas);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Create article' })).toBeDisabled());
		await expect(currentLocation()).toBe('/newarticle');
	},
};

/** A 422 puts each message under its field and moves focus to the first one. Nothing navigates. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleStoreMockHandler(() =>
				rejectWith({
					title_jp: ['The title jp has already been taken.'],
					source_link: ['The source link must be a valid URL.'],
				}),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillRequiredFields(canvas);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await waitFor(() => expect(canvas.getByLabelText('Japanese title')).toHaveFocus());
		await expect(canvas.getByLabelText('Japanese title')).toHaveAccessibleDescription(/already been taken/);
		await expect(canvas.getByLabelText('Source link')).toHaveAccessibleDescription(/valid URL/);
		await expect(currentLocation()).toBe('/newarticle');
	},
};

/** A 500 shows the general error above the form, keeps what was typed and does not navigate. */
export const SubmitError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleStoreMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillRequiredFields(canvas);
		await userEvent.click(canvas.getByRole('button', { name: 'Create article' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('Japanese title')).toHaveValue('新宿駅の新しい改札が完成');
		await expect(canvas.getByRole('button', { name: 'Create article' })).toBeEnabled();
		await expect(currentLocation()).toBe('/newarticle');
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Create article' })).toBeVisible();
	},
};
