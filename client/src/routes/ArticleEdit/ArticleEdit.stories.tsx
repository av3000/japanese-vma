import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import {
	getArticleShowMockHandler,
	getArticleUpdateMockHandler,
	getArticleUpdateResponseMock,
} from '@/api/generated/article/article.msw';
import type { UpdateArticleRequest } from '@/api/generated/model/updateArticleRequest';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { ARTICLE_UUID, articleDetail, failWith, rejectWith } from '../routeFixtures';
import ArticleEditPage from './';

/*
 * The Edit article route on its real hooks: the generated show handler loads the article and the
 * generated PUT handler saves it. The stories assert the body the handler received (only the fields
 * that changed) and where the route navigated afterwards.
 */

const EDIT_PATH = `/articles/${ARTICLE_UUID}/edit`;
const ENGLISH_TITLE = 'New ticket gates completed at Shinjuku Station';

const meta = {
	title: 'Pages/ArticleEdit',
	component: ArticleEditPage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/articles/:article_id/edit', initialEntries: [EDIT_PATH] },
	},
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(articleDetail()));
	},
} satisfies Meta<typeof ArticleEditPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated PUT handler received, newest last; cleared before each story that saves. */
const saved: UpdateArticleRequest[] = [];

const editEnglishTitleAndSave = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(await canvas.findByLabelText('English title'), ' today');
	await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
};

/** The author's article opens in the form; saving sends only the changed field and opens the article. */
export const Edit: Story = {
	beforeEach: ({ msw }) => {
		saved.length = 0;

		msw.use(
			getArticleUpdateMockHandler(async ({ request }) => {
				const body = (await request.json()) as UpdateArticleRequest;
				saved.push(body);

				return getArticleUpdateResponseMock({ title_en: body.title_en ?? undefined });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByLabelText('Japanese title')).toHaveValue('新宿駅の新しい改札が完成');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Edit article' })).toBeVisible();

		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('No changes to save.');
		await expect(saved).toEqual([]);

		await editEnglishTitleAndSave(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/articles/${ARTICLE_UUID}`));
		await expect(saved).toEqual([{ title_en: `${ENGLISH_TITLE} today` }]);
	},
};

/** From 1024px the Settings card sits beside the content card, as on the create page. */
export const TwoColumns: Story = {
	globals: { viewport: { value: 'md' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const title = await canvas.findByLabelText('Japanese title');
		const settings = canvas.getByRole('heading', { name: 'Settings' });

		await expect(settings.getBoundingClientRect().left).toBeGreaterThan(title.getBoundingClientRect().right);
	},
};

/** On a phone the cards stack and nothing scrolls sideways. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await within(canvasElement).findByLabelText('Japanese title');
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};

/** The PUT never answers: the save control is disabled while it is in flight, and the page stays. */
export const Saving: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleUpdateMockHandler(async () => {
				await delay('infinite');

				return getArticleUpdateResponseMock();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await editEnglishTitleAndSave(canvas);

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Save changes' })).toBeDisabled());
		await expect(currentLocation()).toBe(EDIT_PATH);
	},
};

/** A 500 shows the general error and keeps the edit, so the author can retry. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getArticleUpdateMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await editEnglishTitleAndSave(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('English title')).toHaveValue(`${ENGLISH_TITLE} today`);
		await expect(canvas.getByRole('button', { name: 'Save changes' })).toBeEnabled();
	},
};

/** A 422 puts the message under its field. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleUpdateMockHandler(() => rejectWith({ title_en: ['The title en has already been taken.'] })));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await editEnglishTitleAndSave(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await expect(canvas.getByLabelText('English title')).toHaveAccessibleDescription(/already been taken/);
	},
};

/** Another member opens the edit address: the page refuses before showing the form. */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('You do not have permission to edit this article.')).toBeVisible();
		await expect(canvas.queryByLabelText('Japanese title')).toBeNull();
	},
};

/** A 404: the page says the article could not be loaded, with the way back. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getArticleShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Article could not be loaded.')).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Articles' })).toHaveAttribute('href', '/articles');
	},
};
