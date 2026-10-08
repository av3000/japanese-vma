import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, within } from '@storybook/test';
import {
	kanjiOptionsDeck,
	kanjiTypedKunyomiDeck,
	longGlossDeck,
	longWordDeck,
	oneCardDeck,
	radicalReadingDeck,
} from '../fixtures';
import { StudySession } from './index';

const meta = {
	title: 'Features/Flashcards/StudySession',
	component: StudySession,
	parameters: { layout: 'padded' },
	args: {
		deck: kanjiOptionsDeck,
		catalogueHref: '/catalogues/c-1',
		onChangeSetup: fn(),
		onAnswer: fn(),
		onRoundComplete: fn(),
	},
	argTypes: { deck: { control: false } },
} satisfies Meta<typeof StudySession>;

export default meta;

type Story = StoryObj<typeof meta>;

export const KanjiOptions: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('学')).toBeVisible();
		await expect(canvas.getAllByRole('listitem')).toHaveLength(4);
	},
};

/** Picking an option shows the verdict in words and lists every accepted answer. */
export const AfterAWrongAnswer: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: /fire/ }));
		await expect(canvas.getByRole('status')).toHaveTextContent('Not quite');
		await expect(canvas.getByText('learning')).toBeVisible();
	},
};

export const TypedKunyomi: Story = {
	args: { deck: kanjiTypedKunyomiDeck },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByLabelText('Kun’yomi (hiragana)');
		await expect(input).toHaveAttribute('lang', 'ja');
		await userEvent.type(input, 'まなぶ{enter}');
		await expect(canvas.getByRole('status')).toHaveTextContent('Correct');
	},
};

export const RadicalReading: Story = {
	args: { deck: radicalReadingDeck },
};

/** Hostile: ten glosses and a long distractor still fit the four option buttons. */
export const LongGlosses: Story = {
	args: { deck: longGlossDeck },
};

/** Hostile: a one-card deck still gets four options, from the dictionary pool. */
export const OneCard: Story = {
	args: { deck: oneCardDeck },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('listitem')).toHaveLength(4);
	},
};

/** Hostile: a twelve-character word wraps instead of overflowing at 360px. */
export const LongWord: Story = {
	args: { deck: longWordDeck },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

export const Summary: Story = {
	args: { deck: { ...kanjiOptionsDeck, cards: kanjiOptionsDeck.cards.slice(0, 2) } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: /study/ }));
		await userEvent.click(canvas.getByRole('button', { name: 'Next card' }));
		await userEvent.click(canvas.getByRole('button', { name: /fire/ }));
		await userEvent.click(canvas.getByRole('button', { name: 'See results' }));
		await expect(canvas.getByRole('heading', { level: 2 })).toHaveTextContent('1 of 2 correct');
	},
};
