import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import {
	kanjiManyMeanings,
	kanjiNoMeaning,
	kanjiNoReadings,
	kanjiPerson,
} from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { kanjiRelatedSeeds } from '@/components/features/japanese/related/relatedSeeds';
import KanjiContent from './KanjiContent';

/** Kanji detail in the Reading Room layout (UI-DICT-DETAIL, #352), on real dictionary entries. */
const meta = {
	title: 'Pages/KanjiDetails',
	component: KanjiContent,
	parameters: {
		layout: 'fullscreen',
		seeds: kanjiRelatedSeeds(kanjiPerson, { words: 2398, sentences: 49419, articles: 2 }),
	},
	args: { kanji: kanjiPerson },
} satisfies Meta<typeof KanjiContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 人 for a guest: readings, facts, and thousands of related words and sentences behind See all. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const rail = canvas.getByRole('complementary', { name: 'About this kanji' });

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('heading', { level: 1 })).toHaveTextContent('人');
		await expect(within(rail).getByText('Grade 1')).toBeVisible();
		await expect(within(rail).getByRole('link', { name: /人 No. 9/ })).toHaveAttribute('href', '/radical/9');
		await expect(canvas.queryByRole('button', { name: /Save to a catalogue/ })).toBeNull();
		await expect(await canvas.findByRole('button', { name: 'See all 2,398 words' })).toBeVisible();
	},
};

export const SignedIn: Story = {
	parameters: { auth: 'member' },
};

/** 夳: on’yomi only, no meaning, no grade, JLPT or frequency, and nothing related. */
export const NoMeaning: Story = {
	args: { kanji: kanjiNoMeaning },
	parameters: { seeds: kanjiRelatedSeeds(kanjiNoMeaning, { words: 0, sentences: 0, articles: 0 }) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('No English meaning recorded.')).toBeVisible();
		await expect(canvas.queryByText('Kun’yomi')).toBeNull();
		await expect(await canvas.findByText('No words use this kanji yet.')).toBeVisible();
	},
};

/** 鬥: no readings of any kind. */
export const NoReadings: Story = {
	args: { kanji: kanjiNoReadings },
	parameters: { seeds: kanjiRelatedSeeds(kanjiNoReadings, { words: 3, sentences: 0, articles: 0 }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No readings are recorded for this kanji.')).toBeVisible();
	},
};

/** 為: 14 meanings and seven kun’yomi with okurigana. */
export const ManyMeanings: Story = {
	args: { kanji: kanjiManyMeanings },
	parameters: { seeds: kanjiRelatedSeeds(kanjiManyMeanings, { words: 412, sentences: 1630, articles: 1 }) },
};
