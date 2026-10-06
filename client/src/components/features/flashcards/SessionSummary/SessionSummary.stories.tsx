import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, within } from '@storybook/test';
import { SavedListType } from '@/shared/constants/enums';
import { kanjiCards } from '../fixtures';
import { SessionSummary, type SessionAnswer } from './index';

const answer = (index: number, correct: boolean): SessionAnswer => ({
	card: kanjiCards[index],
	given: correct ? kanjiCards[index].displayAnswer : 'fire',
	correct,
	matched: correct ? kanjiCards[index].displayAnswer : null,
	responseMs: 1800,
});

const meta = {
	title: 'Features/Flashcards/SessionSummary',
	component: SessionSummary,
	parameters: { layout: 'padded' },
	args: {
		answers: [answer(0, true), answer(1, false), answer(2, true)],
		attemptNo: 1,
		promptJapanese: true,
		answerJapanese: false,
		catalogueTitle: 'N5 kanji',
		catalogueHref: '/catalogues/c-1',
		saveStatus: 'completed',
		bookmarkCatalogueType: SavedListType.KANJIS,
		onRetryMissed: fn(),
		onRestart: fn(),
		onChangeSetup: fn(),
	},
} satisfies Meta<typeof SessionSummary>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Signed in, one card missed: the bookmark beside it saves it to a catalogue. */
export const SavedWithOneMissed: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 2 })).toHaveTextContent('2 of 3 correct');
		await expect(canvas.getByRole('status')).toHaveTextContent('Results saved.');
		await expect(canvas.getByRole('button', { name: 'Retry missed (1)' })).toBeVisible();
	},
};

export const AllCorrect: Story = {
	args: { answers: [answer(0, true), answer(1, true), answer(2, true)] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.queryByRole('button', { name: /Retry missed/ })).not.toBeInTheDocument();
		await expect(canvas.getByText(/Every card right/)).toBeVisible();
	},
};

export const AllMissed: Story = {
	args: { answers: [answer(0, false), answer(1, false), answer(2, false)] },
};

/** A visitor: no bookmarks, and a sign-in nudge instead of the save line. */
export const Anonymous: Story = {
	args: { saveStatus: 'disabled' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: 'Sign in' })).toBeVisible();
	},
};

export const SaveFailed: Story = {
	args: { saveStatus: 'failed' },
};

export const RetryRound: Story = {
	args: { attemptNo: 2, answers: [answer(1, true)] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('heading', { level: 2 })).toHaveTextContent('retry round 1');
	},
};
