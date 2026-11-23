import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { kanjiCards, longGlossDeck, radicalReadingDeck } from '../fixtures';
import { AnswerFeedback } from './index';

const [kanji, water] = kanjiCards;

const meta = {
	title: 'Features/Flashcards/AnswerFeedback',
	component: AnswerFeedback,
	parameters: { layout: 'padded' },
	args: {
		card: water,
		correct: true,
		matched: 'water',
		given: 'water',
		japanese: false,
		isLast: false,
		onNext: fn(),
	},
} satisfies Meta<typeof AnswerFeedback>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The verdict is a word and a mark, never colour alone; focus moves to Next card, and Enter advances once. */
export const Correct: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Correct');
		await expect(canvas.getByText('Answer')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Next card' })).toHaveFocus();

		await userEvent.keyboard('{Enter}');
		await expect(args.onNext).toHaveBeenCalledTimes(1);
	},
};

/** Wrong with one accepted answer: what the learner gave, then the answer. */
export const Wrong: Story = {
	args: { correct: false, matched: null, given: 'fire' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Not quite · you answered fire');
		await expect(canvas.getByText('Answer')).toBeVisible();
		await expect(canvas.getByText('water')).toBeVisible();
	},
};

/** Wrong against several accepted answers: all of them are listed. */
export const WrongWithAnswer: Story = {
	args: { card: kanji, correct: false, matched: null, given: 'learn' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Accepted answers')).toBeVisible();
		await expect(within(canvas.getByRole('list')).getAllByRole('listitem')).toHaveLength(3);
	},
};

/** A typed kana answer that was wrong, shown in the Japanese face. */
export const WrongJapanese: Story = {
	args: {
		card: radicalReadingDeck.cards[0],
		correct: false,
		matched: null,
		given: 'ぼー',
		japanese: true,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('ぼー')).toHaveAttribute('lang', 'ja');
		await expect(canvas.getByText('ぼう')).toHaveAttribute('lang', 'ja');
	},
};

/** Hostile: ten accepted answers wrap in the list instead of overflowing. */
export const ManyAnswers: Story = {
	args: { card: longGlossDeck.cards[0], correct: true, matched: 'life', given: 'life' },
	globals: { viewport: { value: 'xs' } },
};

/** An empty given answer (a skipped card) leaves out "you answered". */
export const NothingGiven: Story = {
	args: { correct: false, matched: null, given: '' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).not.toHaveTextContent('you answered');
	},
};

/** On the last card Next becomes "See results". */
export const LastCard: Story = {
	args: { isLast: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'See results' })).toBeVisible();
	},
};
