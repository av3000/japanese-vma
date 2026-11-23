import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import type { UpdatePostRequest } from '@/api/generated/model/updatePostRequest';
import { getPostShowMockHandler, getPostUpdateMockHandler } from '@/api/generated/post/post.msw';
import { makePostDetailResource } from '@/components/features/community/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { MEMBER_AS_AUTHOR, POST_UUID, failWith } from '../../routeFixtures';
import PostEditPage from './';

/*
 * The Edit post route on its real hooks: the generated show handler loads the post and the
 * generated PUT handler saves it. The story asserts the body the handler received (only the fields
 * that changed) and where the route navigated afterwards.
 */

const EDIT_PATH = `/community/edit/${POST_UUID}`;

/** A post the signed-in member wrote. */
const ownPost = (overrides: Partial<PostDetailResource> = {}) =>
	makePostDetailResource({ uuid: POST_UUID, author: MEMBER_AS_AUTHOR, ...overrides });

const meta = {
	title: 'Pages/PostEdit',
	component: PostEditPage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/community/edit/:post_id', initialEntries: [EDIT_PATH] },
	},
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(ownPost()));
	},
} satisfies Meta<typeof PostEditPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated PUT handler received, newest last; cleared before each story that saves. */
const saved: UpdatePostRequest[] = [];

const changeTitleAndSave = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(await canvas.findByLabelText('Title'), ' (solved)');
	await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
};

/** The member's own post opens in the form; saving sends only the changed title and opens the post. */
export const Edit: Story = {
	beforeEach: ({ msw }) => {
		saved.length = 0;

		msw.use(
			getPostUpdateMockHandler(async ({ request }) => {
				const body = (await request.json()) as UpdatePostRequest;
				saved.push(body);

				return ownPost({ title: body.title });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByLabelText('Title')).toHaveValue(ownPost().title);
		await expect(canvas.getByRole('heading', { level: 1, name: 'Edit post' })).toBeVisible();

		await changeTitleAndSave(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/community/${POST_UUID}`));
		await expect(saved).toEqual([{ title: `${ownPost().title} (solved)` }]);
	},
};

/** A 404: the route says the post could not be loaded, with the way back. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getPostShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Post could not be loaded.')).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Community' })).toHaveAttribute('href', '/community');
	},
};

/** Another member opens the edit address: the route refuses before showing the form. */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('You do not have permission to edit this post.')).toBeVisible();
		await expect(canvas.queryByLabelText('Title')).toBeNull();
	},
};
