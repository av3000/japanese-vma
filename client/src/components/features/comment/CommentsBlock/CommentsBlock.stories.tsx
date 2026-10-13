import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import {
	getCommentGetArticleCommentsMockHandler,
	getCommentStoreMockHandler,
} from '@/api/generated/comment/comment.msw';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import { users } from '@/storybook/fixtures/users';
import { COMMENTED_ARTICLE, makeComment, threadOf } from '../fixtures';
import CommentsBlock from './';

/*
 * The comment thread under an article, on its real hooks: every request goes through the
 * Orval-generated MSW handlers (#333). Stories override an endpoint with the generated
 * `get…MockHandler(override)` factories, never a hand-written handler.
 */

const meta = {
	title: 'Features/Comments/CommentsBlock',
	component: CommentsBlock,
	parameters: { layout: 'padded' },
	args: { parent: 'article', entityId: COMMENTED_ARTICLE.id, entityUuid: COMMENTED_ARTICLE.uuid },
} satisfies Meta<typeof CommentsBlock>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated POST handler received, newest last; cleared before each story that posts. */
const posted: StoreCommentRequest[] = [];

/** Whatever the generated handler answers (faker, seeded per story, so the same every run). */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const comments = within(canvasElement).getByRole('region', { name: 'Comments' });
		await waitFor(() => expect(within(comments).queryByRole('status')).not.toBeInTheDocument());
		await expect(within(comments).queryByText('Comments could not be loaded. Please try again.')).toBeNull();
	},
};

export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCommentGetArticleCommentsMockHandler(threadOf([])));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText(/Be the first to comment/)).toBeVisible();
	},
};

export const WithComments: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCommentGetArticleCommentsMockHandler(threadOf([1, 2, 3].map((index) => makeComment(index)))));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('@Reader 3')).toBeVisible();
	},
};

/** The thread request never answers. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCommentGetArticleCommentsMockHandler(async () => {
				await delay('infinite');
				return threadOf([]);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading comments…');
	},
};

/** The thread request fails with a 500. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getCommentGetArticleCommentsMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByText('Comments could not be loaded. Please try again.'),
		).toBeVisible();
	},
};

export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: 'login' })).toHaveAttribute('href', '/login');
		await expect(canvas.queryByRole('textbox', { name: 'Your Comment' })).toBeNull();
	},
};

export const Member: Story = {
	parameters: { auth: { user: users.member } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Your Comment' })).toBeVisible();
	},
};

/** A member posts a comment: the generated POST handler receives it, and it heads the thread. */
export const PostsComment: Story = {
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		posted.length = 0;

		msw.use(
			getCommentGetArticleCommentsMockHandler(threadOf([makeComment(1)])),
			getCommentStoreMockHandler(async ({ request }) => {
				const body = (await request.json()) as StoreCommentRequest;
				posted.push(body);

				return makeComment(99, {
					content: body.content,
					author: { id: users.member.id, name: users.member.name, uuid: users.member.uuid },
				});
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const textbox = await canvas.findByRole('textbox', { name: 'Your Comment' });

		await canvas.findByText('@Reader 1');
		await userEvent.type(textbox, '漢字の説明がとても分かりやすかったです。');
		await userEvent.click(canvas.getByRole('button', { name: 'Comment' }));

		await expect(await canvas.findByText('漢字の説明がとても分かりやすかったです。')).toBeVisible();
		await expect(await canvas.findByText('@Hanako')).toBeVisible();
		await expect(posted).toEqual([
			expect.objectContaining({
				content: '漢字の説明がとても分かりやすかったです。',
				entity_id: COMMENTED_ARTICLE.id,
				entity_uuid: COMMENTED_ARTICLE.uuid,
			}),
		]);
	},
};
