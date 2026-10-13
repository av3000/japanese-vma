import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { StorePostRequest } from '@/api/generated/model/storePostRequest';
import { getPostStoreMockHandler } from '@/api/generated/post/post.msw';
import { makePostDetailResource } from '@/components/features/community/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { POST_UUID, failWith, rejectWith } from '../../routeFixtures';
import PostFormPage from './';

/*
 * The New post route on its real mutation: the generated POST handler answers, and the story
 * asserts the body it received and where the route navigated afterwards. The route only has a
 * create form, so there is no entity to load: no loading, not-found or not-owner state exists.
 */

const meta = {
	title: 'Pages/PostForm',
	component: PostFormPage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/newpost', initialEntries: ['/newpost'] },
	},
} satisfies Meta<typeof PostFormPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated POST handler received, newest last; cleared before each story that posts. */
const posted: StorePostRequest[] = [];

const fillAndSubmit = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(canvas.getByLabelText('Title'), 'How do you review kanji from articles?');
	await userEvent.type(canvas.getByLabelText('Text'), 'I save kanji from every article but never go back to them.');
	await userEvent.selectOptions(canvas.getByLabelText('Topic'), '3');
	await userEvent.click(canvas.getByRole('button', { name: 'Publish post' }));
};

/** Fill the form and publish: the body carries the chosen topic, and the route opens the new post. */
export const Create: Story = {
	beforeEach: ({ msw }) => {
		posted.length = 0;

		msw.use(
			getPostStoreMockHandler(async ({ request }) => {
				posted.push((await request.json()) as StorePostRequest);

				return makePostDetailResource({ uuid: POST_UUID });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'New post' })).toBeVisible();

		await fillAndSubmit(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/community/${POST_UUID}`));
		await expect(posted).toEqual([
			{
				title: 'How do you review kanji from articles?',
				content: 'I save kanji from every article but never go back to them.',
				topic: 3,
				tags: [],
			},
		]);
	},
};

/** The POST never answers: the submit control is disabled, and the route stays where it is. */
export const Submitting: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getPostStoreMockHandler(async () => {
				await delay('infinite');

				return makePostDetailResource();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Publish post' })).toBeDisabled());
		await expect(currentLocation()).toBe('/newpost');
	},
};

/** A 422 puts each message under its field and moves focus to the first one. Nothing navigates. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getPostStoreMockHandler(() =>
				rejectWith({
					title: ['The title has already been taken.'],
					type: ['The selected topic is invalid.'],
				}),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Title')).toHaveAccessibleDescription(/already been taken/);
		await expect(canvas.getByLabelText('Topic')).toHaveAccessibleDescription(/topic is invalid/);
		await expect(currentLocation()).toBe('/newpost');
	},
};

/** A 500 shows the general error above the form, keeps what was typed and does not navigate. */
export const SubmitError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getPostStoreMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('Title')).toHaveValue('How do you review kanji from articles?');
		await expect(canvas.getByRole('button', { name: 'Publish post' })).toBeEnabled();
		await expect(currentLocation()).toBe('/newpost');
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Publish post' })).toBeVisible();
	},
};
