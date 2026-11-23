import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import CommentForm from './CommentForm';

const meta = {
	title: 'Features/Comments/CommentForm',
	component: CommentForm,
	parameters: { layout: 'padded' },
	args: {
		onSubmit: fn(async () => undefined),
		isSubmitting: false,
	},
	argTypes: { onSubmit: { control: false }, onCancel: { control: false } },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '48rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof CommentForm>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * A new top-level comment. Empty, the button is disabled and nothing is sent. A valid submit sends
 * the trimmed text once and clears the field.
 */
export const Create: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });
		const submit = canvas.getByRole('button', { name: 'Comment' });

		await expect(canvas.getByText('1000 characters remaining')).toBeVisible();
		await expect(submit).toBeDisabled();

		await userEvent.type(textbox, '  漢字の説明がとても分かりやすかったです。  ');
		await expect(submit).toBeEnabled();
		await userEvent.click(submit);

		await expect(args.onSubmit).toHaveBeenCalledTimes(1);

		await expect(args.onSubmit).toHaveBeenCalledWith('漢字の説明がとても分かりやすかったです。');
		await waitFor(() => expect(textbox).toHaveValue(''));
	},
};

/** A reply under a comment: its own label and placeholder, and a Cancel. */
export const Reply: Story = {
	args: { submitLabel: 'Reply', placeholder: 'Your reply', onCancel: fn() },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.type(canvas.getByRole('textbox', { name: 'Your reply' }), 'ありがとうございます！');
		await userEvent.click(canvas.getByRole('button', { name: 'Reply' }));
		await expect(args.onSubmit).toHaveBeenCalledTimes(1);
		await expect(args.onSubmit).toHaveBeenCalledWith('ありがとうございます！');

		await userEvent.click(canvas.getByRole('button', { name: 'Cancel' }));
		await expect(args.onCancel).toHaveBeenCalledTimes(1);
	},
};

/** Editing starts from the saved text; the button reads Save. */
export const Edit: Story = {
	args: { initialValue: '日本語の勉強に役立ちました。', submitLabel: 'Save', onCancel: fn() },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });

		await expect(textbox).toHaveValue('日本語の勉強に役立ちました。');
		await expect(canvas.getByText('986 characters remaining')).toBeVisible();

		await userEvent.type(textbox, 'とても。');
		await userEvent.click(canvas.getByRole('button', { name: 'Save' }));

		await expect(args.onSubmit).toHaveBeenCalledTimes(1);

		await expect(args.onSubmit).toHaveBeenCalledWith('日本語の勉強に役立ちました。とても。');
	},
};

/**
 * Under the 2-character minimum the button is disabled, so a click cannot send it; the check
 * behind it runs when the form is submitted another way (here `requestSubmit`, as the Enter key in
 * a single-line field would). It announces itself as an alert and marks the field invalid.
 */
export const ValidationError: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });

		await userEvent.type(textbox, '字');
		await expect(canvas.getByRole('button', { name: 'Comment' })).toBeDisabled();
		(textbox as HTMLTextAreaElement).form?.requestSubmit();

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Comment must be at least 2 characters.');
		await expect(textbox).toHaveAttribute('aria-invalid', 'true');
		await expect(args.onSubmit).not.toHaveBeenCalled();

		// Typing again clears the message.
		await userEvent.type(textbox, '字');
		await expect(canvas.queryByRole('alert')).toBeNull();
	},
};

/** The save rejects: the text is kept so nothing is lost, and the failure is announced. */
export const ServerError: Story = {
	args: {
		onSubmit: fn(async () => {
			throw new Error('Request failed with status code 500');
		}),
	},
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const textbox = canvas.getByRole('textbox', { name: 'Your Comment' });

		await userEvent.type(textbox, '漢字の説明がとても分かりやすかったです。');
		await userEvent.click(canvas.getByRole('button', { name: 'Comment' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Failed to save comment. Please try again.');
		await expect(textbox).toHaveValue('漢字の説明がとても分かりやすかったです。');
		await expect(textbox).toHaveAttribute('aria-invalid', 'true');
		await expect(args.onSubmit).toHaveBeenCalledTimes(1);
	},
};

/** The request is in flight: the field and buttons are disabled, so it cannot be sent twice. */
export const Submitting: Story = {
	args: { isSubmitting: true, initialValue: '漢字の説明がとても分かりやすかったです。', onCancel: fn() },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('textbox', { name: 'Your Comment' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: /Comment/ })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Cancel' })).toBeDisabled();
	},
};
