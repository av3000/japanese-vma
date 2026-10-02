import { useState } from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, within } from '@storybook/test';
import { defaultStudyConfig, type StudyConfig } from '@/api/flashcards/deck';
import { SavedListType } from '@/shared/constants/enums';
import { StudySetupForm, type StudyDeckStatus, type StudySetupFormProps } from './index';

const ready: StudyDeckStatus = { kind: 'ready', totalItems: 48, eligibleItems: 45, excludedEmptyAnswerField: 3 };

/** The route owns the config; the story keeps it in local state so the controls stay live. */
const Controlled = (props: StudySetupFormProps) => {
	const [value, setValue] = useState<StudyConfig>(props.value);

	return (
		<StudySetupForm
			{...props}
			value={value}
			onChange={(next) => {
				setValue(next);
				props.onChange(next);
			}}
		/>
	);
};

const meta = {
	title: 'Features/Flashcards/StudySetupForm',
	component: Controlled,
	parameters: { layout: 'padded' },
	args: {
		catalogueType: SavedListType.KANJIS,
		value: defaultStudyConfig(),
		onChange: fn(),
		onStart: fn(),
		deckStatus: ready,
	},
	argTypes: { catalogueType: { control: false }, value: { control: false } },
} satisfies Meta<typeof Controlled>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Kanji: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByLabelText('Card shows')).toHaveValue('character');
		await expect(canvas.getByRole('button', { name: 'Start studying' })).toBeEnabled();
	},
};

export const KanjiTypedKunyomi: Story = {
	args: { value: { ...defaultStudyConfig(), answer: 'kunyomi', mode: 'typed' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByLabelText(/Accept katakana too/)).toBeInTheDocument();
	},
};

export const Words: Story = {
	args: { catalogueType: SavedListType.WORDS, value: { ...defaultStudyConfig(), answer: 'reading' } },
};

export const Radicals: Story = {
	args: { catalogueType: SavedListType.RADICALS },
};

/** Asking for the character: only options mode is offered. */
export const CharacterAnswer: Story = {
	args: { value: { ...defaultStudyConfig(), prompt: 'meaning', answer: 'character' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.queryByRole('option', { name: 'Type the answer' })).not.toBeInTheDocument();
	},
};

export const Counting: Story = {
	args: { deckStatus: { kind: 'loading' } },
};

export const NoEligibleCards: Story = {
	args: {
		value: { ...defaultStudyConfig(), answer: 'kunyomi' },
		deckStatus: {
			kind: 'error',
			title: 'No eligible cards',
			detail: 'No item in this catalogue has a kunyomi to answer with',
		},
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Start studying' })).toBeDisabled();
	},
};

export const Unsupported: Story = {
	args: { catalogueType: SavedListType.SENTENCES },
};
