import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { makeComment, makeReply, makeThreadComment } from '../../fixtures';
import type { CommentActionState } from '../CommentItem/CommentItem';
import CommentList from './CommentList';

const IDLE: CommentActionState = {
	isLikePending: false,
	isEditPending: false,
	isDeletePending: false,
	isReplyPending: false,
};

const meta = {
	title: 'Features/Comments/CommentList',
	component: CommentList,
	parameters: { layout: 'padded' },
	args: {
		comments: [makeComment(1), makeComment(2), makeComment(3)],
		total: 3,
		hasMore: false,
		canReply: true,
		stateFor: (_commentId: number) => IDLE,
		onLike: fn(),
		onDelete: fn(),
		onEdit: fn(),
		onReply: fn(),
	},
	argTypes: {
		stateFor: { control: false },
		onLike: { control: false },
		onDelete: { control: false },
		onEdit: { control: false },
		onReply: { control: false },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '48rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof CommentList>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 3, name: '3 comments' })).toBeVisible();
		await expect(canvas.getAllByRole('listitem')).toHaveLength(3);
		await expect(canvas.getAllByRole('button', { name: 'Reply' })).toHaveLength(3);
	},
};

/** Replies are one level in, under their comment; a comment with more replies than the preview offers a button. */
export const WithReplies: Story = {
	args: {
		comments: [
			makeThreadComment(1, [makeReply(11, 1), makeReply(12, 1)], 5),
			makeThreadComment(2, [makeReply(21, 2)]),
			makeComment(3),
		],
		total: 3,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('@Reader 11')).toBeVisible();
		await expect(canvas.getByText('@Reader 21')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Show 3 more replies' })).toBeVisible();
	},
};

/**
 * The thread is bigger than the loaded pages: the heading counts the thread, a line says how many
 * are shown, and Show more asks for the next page.
 */
export const Paged: Story = {
	args: { total: 57, hasMore: true, onShowMore: fn() },
	play: async ({ args, canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 3, name: '57 comments' })).toBeVisible();
		await expect(canvas.getByText('Showing 3 of 57 comments')).toBeVisible();

		await userEvent.click(canvas.getByRole('button', { name: 'Show more comments' }));
		await expect(args.onShowMore).toHaveBeenCalledTimes(1);
	},
};

/** The next page failed: the reader is told, and the button stays to try again. */
export const ShowMoreError: Story = {
	args: { total: 57, hasMore: true, loadMoreFailed: true },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('alert')).toHaveTextContent('More comments could not be loaded.');
		await expect(canvas.getByRole('button', { name: 'Show more comments' })).toBeEnabled();
	},
};

/** A page whose outline puts comments under an `h1` gets the count as an `h2`. */
export const HeadingLevelTwo: Story = {
	args: { headingLevel: 2 },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('heading', { level: 2, name: '3 comments' })).toBeVisible();
	},
};

export const Empty: Story = {
	args: { comments: [], total: 0 },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 3, name: '0 comments' })).toBeVisible();
		await expect(canvas.getByText('Be the first to comment')).toBeVisible();
		await expect(canvas.queryByRole('listitem')).toBeNull();
	},
};

/**
 * The list has no load error of its own (CommentsBlock shows that, see its `Error` story). What it
 * does surface is a failed action: the row that failed carries the message, the others stay quiet.
 */
export const ActionError: Story = {
	name: 'Error',
	args: {
		stateFor: (commentId) =>
			commentId === 2 ? { ...IDLE, error: 'You are not allowed to delete this comment.' } : IDLE,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('alert')).toHaveTextContent('You are not allowed to delete this comment.');
		await expect(canvas.getAllByRole('alert')).toHaveLength(1);
	},
};

const UNBROKEN_JAPANESE =
	'日本語の文章には単語の間にスペースがないので、とても長い一文でも途中で折り返される必要があります。'.repeat(4);

/** Long Japanese with no spaces wraps inside the comment; nothing scrolls sideways. */
export const JapaneseText: Story = {
	args: {
		comments: [
			makeComment(1, { content: UNBROKEN_JAPANESE }),
			makeThreadComment(2, [makeReply(21, 2, { content: UNBROKEN_JAPANESE })]),
		],
		total: 2,
	},
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getAllByText(UNBROKEN_JAPANESE)).toHaveLength(2);

		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
