import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import {
	getCommentDestroyMockHandler,
	getCommentGetArticleCommentsMockHandler,
	getCommentRepliesMockHandler,
	getCommentStoreMockHandler,
	getCommentUpdateMockHandler,
} from '@/api/generated/comment/comment.msw';
import { getLikeLikeInstanceMockHandler } from '@/api/generated/like/like.msw';
import type { CommentResource } from '@/api/generated/model/commentResource';
import type { LikeInstanceRequest } from '@/api/generated/model/likeInstanceRequest';
import type { StoreCommentRequest } from '@/api/generated/model/storeCommentRequest';
import type { UpdateCommentRequest } from '@/api/generated/model/updateCommentRequest';
import { users } from '@/storybook/fixtures/users';
import {
	COMMENTED_ARTICLE,
	OWN_VIEWER,
	authorOf,
	makeComment,
	makeReply,
	makeThreadComment,
	repliesOf,
	threadOf,
} from '../fixtures';
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
		const comments = within(canvasElement);
		await waitFor(() => expect(comments.queryByRole('status')).not.toBeInTheDocument());
		await expect(comments.queryByText('Comments could not be loaded. Please try again.')).toBeNull();
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

/** Two comments, one with replies. The thread carries a preview of each subtree, indented one level. */
export const WithReplies: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCommentGetArticleCommentsMockHandler(
				threadOf([
					makeThreadComment(1, [makeReply(11, 1), makeReply(12, 1, { content: 'ありがとうございます！' })]),
					makeComment(2),
				]),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('@Reader 11')).toBeVisible();
		await expect(canvas.getByText('ありがとうございます！')).toBeVisible();
		await expect(canvas.getByText('@Reader 2')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: /more repl/ })).toBeNull();
	},
};

/**
 * A long thread: the first page holds 20 of 57 comments (the block has no "load more" for top-level
 * comments, it says how many are shown), and one comment has 8 replies of which 3 are previewed.
 * "Show 5 more replies" fetches the rest through the replies handler.
 */
export const LongThread: Story = {
	beforeEach: ({ msw }) => {
		const previewed = [101, 102, 103].map((id) => makeReply(id, 1));
		const everyReply = [101, 102, 103, 104, 105, 106, 107, 108].map((id) => makeReply(id, 1));
		const page = [
			makeThreadComment(1, previewed, everyReply.length),
			...Array.from({ length: 19 }, (_, index) => makeComment(index + 2)),
		];

		msw.use(
			getCommentGetArticleCommentsMockHandler(threadOf(page, 57)),
			getCommentRepliesMockHandler(repliesOf(everyReply)),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Showing 20 of 57 comments')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 3, name: '57 comments' })).toBeVisible();
		await expect(canvas.queryByText('@Reader 108')).toBeNull();

		await userEvent.click(canvas.getByRole('button', { name: 'Show 5 more replies' }));

		await expect(await canvas.findByText('@Reader 108')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: /more repl/ })).toBeNull();
	},
};

const ownComment = (overrides: Partial<CommentResource> = {}) =>
	makeComment(1, {
		content: '日本語の勉強に役立ちました。',
		author: authorOf(users.member),
		viewer: OWN_VIEWER,
		likes_count: 4,
		...overrides,
	});

/** Signed in as the author: the API marks the comment editable and deletable, so both controls show. */
export const OwnComment: Story = {
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		msw.use(getCommentGetArticleCommentsMockHandler(threadOf([ownComment()])));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('@Hanako')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Edit this comment' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Delete this comment' })).toBeVisible();
	},
};

/** Signed in as someone else: the API sends `can_edit` and `can_delete` as false, so neither control shows. */
export const OthersComment: Story = {
	parameters: { auth: { user: users.other } },
	beforeEach: ({ msw }) => {
		msw.use(
			getCommentGetArticleCommentsMockHandler(
				threadOf([ownComment({ viewer: { is_liked: false, can_edit: false, can_delete: false } })]),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('@Hanako')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: 'Edit this comment' })).toBeNull();
		await expect(canvas.queryByRole('button', { name: 'Delete this comment' })).toBeNull();
		await expect(canvas.getByRole('button', { name: 'Like this comment' })).toBeVisible();
	},
};

/** A locked parent: a status explains it, and neither the form nor Reply is offered. */
export const Locked: Story = {
	args: { isLocked: true },
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		msw.use(getCommentGetArticleCommentsMockHandler(threadOf([makeComment(1), makeComment(2)])));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('This post is locked and new comments are not allowed.')).toBeVisible();
		await expect(await canvas.findByText('@Reader 2')).toBeVisible();
		await expect(canvas.queryByRole('textbox')).toBeNull();
		await expect(canvas.queryByRole('button', { name: 'Reply' })).toBeNull();
	},
};

/** What the generated PUT handler received, newest last. */
const edited: Array<{ uuid: string; body: UpdateCommentRequest }> = [];

/** The author edits their comment: the PUT carries the new text, and the thread shows it. */
export const EditsOwnComment: Story = {
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		edited.length = 0;

		msw.use(
			getCommentGetArticleCommentsMockHandler(threadOf([ownComment(), makeComment(2)])),
			getCommentUpdateMockHandler(async ({ request, params }) => {
				const body = (await request.json()) as UpdateCommentRequest;
				edited.push({ uuid: String(params.uuid), body });

				return ownComment({ content: body.content });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('日本語の勉強に役立ちました。');

		await userEvent.click(canvas.getByRole('button', { name: 'Edit this comment' }));

		// The compose form above the thread has the same accessible name; the edit form is the one holding the text.
		const textbox = canvas.getByDisplayValue('日本語の勉強に役立ちました。');
		await userEvent.clear(textbox);
		await userEvent.type(textbox, '漢字の例文がとても役に立ちました。');
		await userEvent.click(canvas.getByRole('button', { name: 'Save' }));

		// The edit form's textarea holds the same text until the save lands, so wait for the form to close first.
		await waitFor(() => expect(canvas.queryByRole('button', { name: 'Save' })).toBeNull());
		await expect(canvas.getByText('漢字の例文がとても役に立ちました。', { ignore: 'textarea' })).toBeVisible();
		await waitFor(() =>
			expect(edited).toEqual([{ uuid: 'comment-1', body: { content: '漢字の例文がとても役に立ちました。' } }]),
		);
	},
};

/** The uuids the generated DELETE handler received. */
const deleted: string[] = [];

/**
 * The author deletes their comment. The component asks no confirmation (the issue expected one): the
 * click sends the DELETE and the comment leaves the thread at once.
 */
export const DeletesOwnComment: Story = {
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		deleted.length = 0;

		msw.use(
			getCommentGetArticleCommentsMockHandler(threadOf([ownComment(), makeComment(2)])),
			getCommentDestroyMockHandler(({ params }) => {
				deleted.push(String(params.uuid));

				return '';
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('日本語の勉強に役立ちました。');

		await userEvent.click(canvas.getByRole('button', { name: 'Delete this comment' }));

		await waitFor(() => expect(canvas.queryByText('日本語の勉強に役立ちました。')).toBeNull());
		await expect(canvas.getByText('@Reader 2')).toBeVisible();
		await waitFor(() => expect(deleted).toEqual(['comment-1']));
	},
};

/** The bodies the generated like handler received. */
const likes: LikeInstanceRequest[] = [];

/** A member likes a comment: the POST names the comment type and id, and the served count replaces the old one. */
export const LikesComment: Story = {
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		likes.length = 0;

		msw.use(
			getCommentGetArticleCommentsMockHandler(threadOf([makeComment(1, { likes_count: 4 })])),
			getLikeLikeInstanceMockHandler(async ({ request }) => {
				likes.push((await request.json()) as LikeInstanceRequest);

				return { is_liked: true, likes_count: 5 };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('4 likes');

		await userEvent.click(canvas.getByRole('button', { name: 'Like this comment' }));

		await expect(await canvas.findByText('5 likes')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Unlike this comment' })).toHaveAttribute(
			'aria-pressed',
			'true',
		);
		// The count updates optimistically, before the request lands: wait for the handler.
		await waitFor(() => expect(likes).toEqual([{ template_id: 10, real_object_id: 1 }]));
	},
};

/** 25 comments in pages of 20: Show more loads page 2 under page 1, then goes away. */
export const ShowMore: Story = {
	beforeEach: ({ msw }) => {
		const all = Array.from({ length: 25 }, (_, index) => makeComment(index + 1));

		msw.use(
			getCommentGetArticleCommentsMockHandler(({ request }) => {
				const page = Number(new URL(request.url).searchParams.get('page') ?? '1');
				const items = all.slice((page - 1) * 20, page * 20);

				return {
					items,
					pagination: { page, per_page: 20, total: 25, last_page: 2, has_more: page < 2 },
				};
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Showing 20 of 25 comments')).toBeVisible();
		await expect(canvas.queryByText('@Reader 21')).toBeNull();

		await userEvent.click(canvas.getByRole('button', { name: 'Show more comments' }));

		await expect(await canvas.findByText('@Reader 25')).toBeVisible();
		await expect(canvas.getByText('@Reader 1')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: 'Show more comments' })).toBeNull();
		await expect(canvas.getByRole('heading', { level: 3, name: '25 comments' })).toBeVisible();
	},
};
