import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fireEvent, fn, userEvent, within } from 'storybook/test';
import { AnswerInput } from './index';

/*
 * The input only collects and submits. Whether the answer was right is the study session's call, so
 * there are no Correct or Wrong states here; they appear as `AnswerFeedback` instead.
 */

const meta = {
	title: 'Features/Flashcards/AnswerInput',
	component: AnswerInput,
	parameters: { layout: 'padded' },
	args: { label: 'Meaning (English)', japanese: false, onSubmit: fn() },
} satisfies Meta<typeof AnswerInput>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Starts empty and focused; Check stays disabled, and Enter on an empty field submits nothing. */
export const Empty: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByLabelText('Meaning (English)')).toHaveFocus();
		await expect(canvas.getByRole('button', { name: 'Check' })).toBeDisabled();

		await userEvent.keyboard('{Enter}');
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

/** Enter submits the trimmed answer once. */
export const Typed: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.type(canvas.getByLabelText('Meaning (English)'), '  study ');
		await expect(canvas.getByRole('button', { name: 'Check' })).toBeEnabled();

		await userEvent.keyboard('{Enter}');
		await expect(args.onSubmit).toHaveBeenCalledTimes(1);
		await expect(args.onSubmit).toHaveBeenCalledWith('study');
	},
};

/** The Check button submits too. */
export const CheckButton: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.type(canvas.getByLabelText('Meaning (English)'), 'water');
		await userEvent.click(canvas.getByRole('button', { name: 'Check' }));
		await expect(args.onSubmit).toHaveBeenCalledWith('water');
	},
};

/** A kana answer: `lang="ja"` so the IME and the Japanese font follow. */
export const Japanese: Story = {
	args: { label: 'Kun’yomi (hiragana)', japanese: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByLabelText('Kun’yomi (hiragana)');

		await expect(input).toHaveAttribute('lang', 'ja');
		await userEvent.type(input, 'まなぶ{enter}');
		await expect(args.onSubmit).toHaveBeenCalledWith('まなぶ');
	},
};

/** Confirming a kana conversion also fires Enter; while composing it must not grade the half-typed answer. */
export const ImeComposition: Story = {
	args: { label: 'Kun’yomi (hiragana)', japanese: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByLabelText('Kun’yomi (hiragana)');

		await userEvent.type(input, 'まな');
		await fireEvent.compositionStart(input);

		// The Enter that confirms the conversion.
		await userEvent.keyboard('{Enter}');
		await expect(args.onSubmit).not.toHaveBeenCalled();

		await fireEvent.compositionEnd(input);
		await userEvent.keyboard('{Enter}');
		await expect(args.onSubmit).toHaveBeenCalledTimes(1);
		await expect(args.onSubmit).toHaveBeenCalledWith('まな');
	},
};

/** While the card is graded the field and the button are off. */
export const Disabled: Story = {
	args: { disabled: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByLabelText('Meaning (English)')).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Check' })).toBeDisabled();
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

/** A long label wraps rather than pushing the field off a 360px screen. */
export const LongLabel: Story = {
	args: { label: 'Reading (kana or romaji), as in the radical’s name in the dictionary', japanese: true },
	globals: { viewport: { value: 'xs' } },
};
