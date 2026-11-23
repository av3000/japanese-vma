import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { FlashcardField } from '@/api/generated/model/flashcardField';
import { kanjiCards, longGlossDeck, longWordDeck, radicalReadingDeck } from '../fixtures';
import { FlashcardPrompt } from './index';

const [kanji] = kanjiCards;
const [word] = longWordDeck.cards;
const [radical] = radicalReadingDeck.cards;
const [longGloss] = longGlossDeck.cards;

const meta = {
	title: 'Features/Flashcards/FlashcardPrompt',
	component: FlashcardPrompt,
	parameters: { layout: 'padded' },
	args: { card: kanji, field: FlashcardField.character },
} satisfies Meta<typeof FlashcardPrompt>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A kanji: one big glyph, then JLPT level, strokes and grade on one muted line. */
export const Kanji: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('学')).toHaveAttribute('lang', 'ja');
		await expect(canvas.getByText('8 strokes')).toBeVisible();
		await expect(canvas.getByText('Grade 1')).toBeVisible();
	},
};

/** A word has no stroke count or grade, and keeps the dictionary's part-of-speech hint. */
export const Word: Story = {
	args: { card: { ...word, jlpt: '3' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('国際連合教育科学文化機関')).toBeVisible();
		await expect(canvas.getByText('noun (common) (futsuumeishi)')).toBeVisible();
		await expect(canvas.queryByText(/stroke/)).not.toBeInTheDocument();
	},
};

/** A radical with one stroke: the singular, and no JLPT level or grade. */
export const Radical: Story = {
	args: { card: radical },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('1 stroke')).toBeVisible();
	},
};

/** A reading as the prompt: kana in the Japanese face. */
export const Reading: Story = {
	args: {
		card: { ...kanji, promptText: 'まなぶ', promptHint: 'verb', strokes: null, grade: null },
		field: FlashcardField.reading,
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('まなぶ')).toHaveAttribute('lang', 'ja');
	},
};

/** The meaning as the prompt is English: no `lang="ja"`. */
export const Meaning: Story = {
	args: { card: { ...kanji, promptText: 'study' }, field: FlashcardField.meaning },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('study')).not.toHaveAttribute('lang');
	},
};

/** Hostile: a gloss of ten meanings used as the prompt wraps instead of overflowing at 360px. */
export const LongGloss: Story = {
	args: {
		card: { ...longGloss, promptText: longGloss.acceptedAnswers.join(', ') },
		field: FlashcardField.meaning,
	},
	globals: { viewport: { value: 'xs' } },
};

/** Hostile: a twelve-character word at 360px. */
export const LongWordMobile: Story = {
	args: { card: word },
	globals: { viewport: { value: 'xs' } },
};
