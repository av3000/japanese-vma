import { useState } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import type { ApiComment } from '@/api/comments';
import { users } from '@/storybook/fixtures/users';
import { OWN_VIEWER, authorOf, makeComment } from '../../fixtures';
import CommentItem, { type CommentActionState } from './CommentItem';

const IDLE: CommentActionState = {
	isLikePending: false,
	isEditPending: false,
	isDeletePending: false,
	isReplyPending: false,
};

const OTHERS_COMMENT = makeComment(1, { likes_count: 4 });

const OWN_COMMENT = makeComment(2, {
	author: authorOf(users.member),
	viewer: OWN_VIEWER,
	content: '日本語の勉強に役立ちました。',
	likes_count: 4,
});

const meta = {
	title: 'Features/Comments/CommentItem',
	component: CommentItem,
	parameters: { layout: 'padded' },
	args: {
		comment: OTHERS_COMMENT,
		canReply: true,
		state: IDLE,
		onLike: fn(),
		onDelete: fn(),
		onEdit: fn(async () => undefined),
		onReply: fn(async () => undefined),
	},
	argTypes: { onLike: { control: false }, onDelete: { control: false }, onEdit: { control: false } },
	decorators: [
		// An item is a list row; the list is the parent's, so the story supplies one.
		(Story) => (
			<ul style={{ maxWidth: '48rem', margin: 0, padding: 0, listStyle: 'none' }}>
				<Story />
			</ul>
		),
	],
} satisfies Meta<typeof CommentItem>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The author's own comment: the API says they can edit and delete it. */
export const Own: Story = {
	args: { comment: OWN_COMMENT },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('@Hanako')).toBeVisible();
		await expect(canvas.getByText('4 likes')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Edit this comment' })).toBeEnabled();

		await userEvent.click(canvas.getByRole('button', { name: 'Delete this comment' }));
		await expect(args.onDelete).toHaveBeenCalledTimes(1);
	},
};

/** Someone else's comment: no edit or delete, but like and reply. A like calls the handler. */
export const Others: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('@Reader 1')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: 'Edit this comment' })).toBeNull();
		await expect(canvas.queryByRole('button', { name: 'Delete this comment' })).toBeNull();
		await expect(canvas.getByRole('button', { name: 'Like this comment' })).toHaveAttribute(
			'aria-pressed',
			'false',
		);

		await userEvent.click(canvas.getByRole('button', { name: 'Like this comment' }));
		await expect(args.onLike).toHaveBeenCalledTimes(1);
	},
};

/** A guest cannot reply: the parent passes `canReply` false and no reply handler. Reading and the like count remain. */
export const Guest: Story = {
	args: { canReply: false, onReply: undefined },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('@Reader 1')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: 'Reply' })).toBeNull();
	},
};

/** A like the viewer already gave shows as pressed. */
export const Liked: Story = {
	args: { comment: { ...OTHERS_COMMENT, viewer: { ...OTHERS_COMMENT.viewer, is_liked: true } } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Unlike this comment' })).toHaveAttribute(
			'aria-pressed',
			'true',
		);
	},
};

/** Edit swaps the text for a form holding it. Save sends the new text and closes the form; Cancel closes it. */
export const Editing: Story = {
	args: { comment: OWN_COMMENT },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Edit this comment' }));
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });
		await expect(textbox).toHaveValue('日本語の勉強に役立ちました。');
		await expect(canvas.queryByRole('button', { name: 'Edit this comment' })).toBeNull();

		await userEvent.clear(textbox);
		await userEvent.type(textbox, '漢字の例文がとても役に立ちました。');
		await userEvent.click(canvas.getByRole('button', { name: 'Save' }));

		await expect(args.onEdit).toHaveBeenCalledTimes(1);

		await expect(args.onEdit).toHaveBeenCalledWith('漢字の例文がとても役に立ちました。');
		await waitFor(() => expect(canvas.queryByRole('textbox')).toBeNull());
	},
};

/** Cancelling an edit puts the original text back without calling the handler. */
export const EditCancelled: Story = {
	args: { comment: OWN_COMMENT },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Edit this comment' }));
		await userEvent.type(canvas.getByRole('textbox', { name: 'Your Comment' }), ' 追記');
		await userEvent.click(canvas.getByRole('button', { name: 'Cancel' }));

		await expect(canvas.queryByRole('textbox')).toBeNull();
		await expect(canvas.getByText('日本語の勉強に役立ちました。')).toBeVisible();
		await expect(args.onEdit).not.toHaveBeenCalled();
	},
};

/** Reply opens a form under the comment; the Reply button steps aside while it is open. */
export const Replying: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Reply' }));
		// Only the form's own button is left, and it waits for text.
		await expect(canvas.getByRole('button', { name: 'Reply' })).toBeDisabled();

		await userEvent.type(canvas.getByRole('textbox', { name: 'Your reply' }), 'ありがとうございます！');
		await userEvent.click(canvas.getByRole('button', { name: 'Reply' }));

		await expect(args.onReply).toHaveBeenCalledTimes(1);

		await expect(args.onReply).toHaveBeenCalledWith('ありがとうございます！');
		await waitFor(() => expect(canvas.queryByRole('textbox')).toBeNull());
	},
};

/** An action on this comment is in flight: edit, delete and like are disabled, so none can be sent twice. */
export const Busy: Story = {
	args: { comment: OWN_COMMENT, state: { ...IDLE, isLikePending: true, isEditPending: true, isDeletePending: true } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('button', { name: 'Edit this comment' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Delete this comment' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Like this comment' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Reply' })).toBeEnabled();
	},
};

/** A reply being sent: the reply form is disabled and its button shows the busy state. */
export const ReplySending: Story = {
	args: { state: { ...IDLE, isReplyPending: true } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Reply' }));

		await expect(canvas.getByRole('textbox', { name: 'Your reply' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Cancel' })).toBeDisabled();
	},
};

/** The last action failed: the message sits under the text, as an alert. */
export const ActionError: Story = {
	args: { comment: OWN_COMMENT, state: { ...IDLE, error: 'You are not allowed to delete this comment.' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('alert')).toHaveTextContent(
			'You are not allowed to delete this comment.',
		);
	},
};

/** Holds the comment the way the thread cache does, so a saved edit shows. */
const SavesEdits = ({ comment, ...props }: React.ComponentProps<typeof CommentItem>) => {
	const [current, setCurrent] = useState<ApiComment>(comment as ApiComment);

	return (
		<CommentItem
			{...props}
			comment={current}
			onEdit={async (content) => {
				await props.onEdit(content);
				setCurrent({ ...current, content, updated_at: '2026-05-27T09:00:00+00:00' });
			}}
		/>
	);
};

/**
 * After a saved edit the new text replaces the old. The item does not mark a comment as edited, and
 * shows only the date it was created (the issue expected an edited marker); `updated_at` is not read.
 */
export const Edited: Story = {
	args: { comment: OWN_COMMENT },
	render: (args) => <SavesEdits {...args} />,
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Edit this comment' }));
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });
		await userEvent.clear(textbox);
		await userEvent.type(textbox, '修正しました。');
		await userEvent.click(canvas.getByRole('button', { name: 'Save' }));

		await expect(await canvas.findByText('修正しました。')).toBeVisible();
		await expect(args.onEdit).toHaveBeenCalledWith('修正しました。');
		await expect(canvas.queryByText('日本語の勉強に役立ちました。')).toBeNull();
		await expect(canvas.getByText(/May 26/)).toHaveAttribute('datetime', OWN_COMMENT.created_at);
	},
};
