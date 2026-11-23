import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { API_ERROR_MESSAGES } from '@/api/apiError';
import { getPostDestroyMockHandler } from '@/api/generated/post/post.msw';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { POST_AUTHOR } from '../fixtures';
import PostOwnerActions from './';

const OWNER_ID = POST_AUTHOR.id;

const meta = {
	title: 'Features/Community/PostOwnerActions',
	component: PostOwnerActions,
	parameters: {
		layout: 'padded',
		auth: { user: { ...users.member, id: OWNER_ID } },
		router: { initialEntries: ['/community/post-41'] },
	},
	args: {
		postId: 41,
		uuid: 'post-41',
		title: 'How do you remember the difference between は and が?',
		authorId: OWNER_ID,
		isLocked: false,
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '16rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof PostOwnerActions>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Opens the delete dialog from the rail and waits for its panel to finish fading in. */
const openDeleteDialog = async (canvasElement: HTMLElement) => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Delete post' }));

	const dialog = within(await within(document.body).findByRole('dialog', { name: 'Delete post' }));
	await waitFor(() => expect(dialog.getByRole('heading')).toBeVisible());

	return dialog;
};

/** The story's wrapper is all that is left: no text, no control, no dialog. */
const expectNothingRendered = async (canvasElement: HTMLElement) => {
	await expect(canvasElement).toHaveTextContent(/^$/);
	await expect(within(canvasElement).queryByRole('button')).toBeNull();
	await expect(within(canvasElement).queryByRole('link')).toBeNull();
	await expect(within(document.body).queryByRole('dialog')).toBeNull();
};

/** The author sees their own controls: Edit, which links to the edit form, and Delete. */
export const Owner: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { name: 'Your post' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Edit post' })).toHaveAttribute(
			'href',
			'/community/edit/post-41',
		);
		await expect(canvas.getByRole('button', { name: 'Delete post' })).toBeVisible();
		await expect(canvas.queryByRole('heading', { name: 'Moderation' })).toBeNull();
	},
};

/** Anyone else gets nothing: no heading, no controls, no dialog. */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	play: async ({ canvasElement }) => {
		await expectNothingRendered(canvasElement);
	},
};

/** A guest gets nothing either. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		await expectNothingRendered(canvasElement);
	},
};

/** The uuids the generated DELETE handler received. */
const deleted: string[] = [];

/** Confirming sends the DELETE for the post and returns to the community list. */
export const DeletesPost: Story = {
	beforeEach: ({ msw }) => {
		deleted.length = 0;

		msw.use(
			getPostDestroyMockHandler(({ params }) => {
				deleted.push(String(params.uuid));

				return '';
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(currentLocation()).toBe('/community/post-41');

		const dialog = await openDeleteDialog(canvasElement);
		await expect(dialog.getByText('How do you remember the difference between は and が?')).toBeVisible();
		await userEvent.click(dialog.getByRole('button', { name: 'Yes, Delete Post' }));

		await waitFor(() => expect(currentLocation()).toBe('/community'));
		await expect(deleted).toEqual(['post-41']);
	},
};

/** The DELETE has not answered: the confirm button reads Deleting... and is disabled, so it is not sent twice. */
export const Deleting: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getPostDestroyMockHandler(async () => {
				await delay('infinite');

				return '';
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDeleteDialog(canvasElement);
		await userEvent.click(dialog.getByRole('button', { name: 'Yes, Delete Post' }));

		await expect(await dialog.findByRole('button', { name: 'Deleting...' })).toBeDisabled();
		await expect(currentLocation()).toBe('/community/post-41');
	},
};

/** The DELETE is refused: the dialog stays open and the reason shows in the rail. */
export const DeleteError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getPostDestroyMockHandler(() => {
				throw new Response(null, { status: 403 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const dialog = await openDeleteDialog(canvasElement);
		await userEvent.click(dialog.getByRole('button', { name: 'Yes, Delete Post' }));

		await expect(await within(canvasElement).findByRole('alert')).toHaveTextContent(API_ERROR_MESSAGES.forbidden);
		await expect(dialog.getByRole('button', { name: 'Yes, Delete Post' })).toBeEnabled();
		await expect(currentLocation()).toBe('/community/post-41');
	},
};
