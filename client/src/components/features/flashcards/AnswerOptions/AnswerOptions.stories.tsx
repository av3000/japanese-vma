import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { longGlossDeck } from '../fixtures';
import { AnswerOptions } from './index';

/*
 * The options hold no picked or revealed state: the study session disables them and shows the
 * verdict as `AnswerFeedback`. Keys 1 to 4 pick an option; the arrow keys do nothing special, focus
 * moves with Tab as in any group of buttons.
 */

const meta = {
	title: 'Features/Flashcards/AnswerOptions',
	component: AnswerOptions,
	parameters: { layout: 'padded' },
	args: { options: ['one', 'study', 'water', 'fire'], japanese: false, onSelect: fn() },
} satisfies Meta<typeof AnswerOptions>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Four numbered buttons; a click picks one. */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('button')).toHaveLength(4);
		await expect(canvas.getByRole('button', { name: /study/ })).toHaveAttribute('aria-keyshortcuts', '2');

		await userEvent.click(canvas.getByRole('button', { name: /water/ }));
		await expect(args.onSelect).toHaveBeenCalledWith('water');
	},
};

/** The number on a button is also its key, from anywhere on the page. */
export const NumberKeys: Story = {
	play: async ({ args }) => {
		await userEvent.keyboard('2');
		await expect(args.onSelect).toHaveBeenLastCalledWith('study');

		await userEvent.keyboard('4');
		await expect(args.onSelect).toHaveBeenLastCalledWith('fire');

		// Out of range, and a modified key, pick nothing.
		await userEvent.keyboard('5');
		await userEvent.keyboard('{Control>}1{/Control}');
		await expect(args.onSelect).toHaveBeenCalledTimes(2);
	},
};

/** Tab walks the four buttons in order; Enter picks the focused one. */
export const KeyboardFocus: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const buttons = canvas.getAllByRole('button');

		await userEvent.tab();
		await expect(buttons[0]).toHaveFocus();
		await userEvent.tab();
		await expect(buttons[1]).toHaveFocus();

		await userEvent.keyboard('{Enter}');
		await expect(args.onSelect).toHaveBeenCalledWith('study');
	},
};

/** Number keys are ignored while the learner types in a field elsewhere on the page. */
export const IgnoresKeysWhileTyping: Story = {
	render: (args) => (
		<>
			<label>
				Notes <input type="text" />
			</label>
			<AnswerOptions {...args} />
		</>
	),
	play: async ({ canvasElement, args }) => {
		await userEvent.type(within(canvasElement).getByLabelText('Notes'), '123');
		await expect(args.onSelect).not.toHaveBeenCalled();
	},
};

/** Kanji options get `lang="ja"` and the Japanese font. */
export const Japanese: Story = {
	args: { options: ['学', '水', '一', '火'], japanese: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('学')).toHaveAttribute('lang', 'ja');
	},
};

/** Hostile: a long distractor wraps inside its button at 360px. */
export const LongOptions: Story = {
	args: { options: longGlossDeck.cards[0].options ?? [] },
	globals: { viewport: { value: 'xs' } },
};

/** After a pick the session disables the options, and the number keys stop. */
export const Disabled: Story = {
	args: { disabled: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		for (const button of canvas.getAllByRole('button')) {
			await expect(button).toBeDisabled();
		}
		await userEvent.keyboard('1');
		await expect(args.onSelect).not.toHaveBeenCalled();
	},
};
