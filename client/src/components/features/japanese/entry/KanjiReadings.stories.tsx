import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { kanjiManyMeanings, kanjiNoMeaning, kanjiNoReadings, kanjiPerson } from '../related/dictionaryDetail.fixtures';
import { KanjiReadings } from './KanjiReadings';

const meta = {
	title: 'Features/Japanese/Entry/KanjiReadings',
	component: KanjiReadings,
	parameters: { layout: 'padded' },
	args: {
		onyomi: kanjiPerson.onyomi,
		kunyomi: kanjiPerson.kunyomi,
		nanori: kanjiPerson.nanori,
	},
} satisfies Meta<typeof KanjiReadings>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 人: on’yomi, kun’yomi with suffix marks, and name readings. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('region', { name: 'Readings' })).toBeVisible();
		await expect(canvas.getByText('On’yomi')).toBeVisible();
		await expect(canvas.getByText('Kun’yomi')).toBeVisible();
		await expect(canvas.getByText('Name readings')).toBeVisible();
		await expect(canvas.getByText('ジン')).toBeVisible();
	},
};

/** Hostile: 為 has seven kun’yomi, most with okurigana set apart by a dot. */
export const ManyReadings: Story = {
	args: {
		onyomi: kanjiManyMeanings.onyomi,
		kunyomi: kanjiManyMeanings.kunyomi,
		nanori: kanjiManyMeanings.nanori,
	},
};

/** On’yomi only: the API sends `["-"]` for the others, and those rows are left out. */
export const OnyomiOnly: Story = {
	args: { onyomi: kanjiNoMeaning.onyomi, kunyomi: kanjiNoMeaning.kunyomi, nanori: kanjiNoMeaning.nanori },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('On’yomi')).toBeVisible();
		await expect(canvas.queryByText('Kun’yomi')).not.toBeInTheDocument();
		await expect(canvas.queryByText('Name readings')).not.toBeInTheDocument();
	},
};

/** 鬥 has no readings at all: one line says so. */
export const NoReadings: Story = {
	args: { onyomi: kanjiNoReadings.onyomi, kunyomi: kanjiNoReadings.kunyomi, nanori: kanjiNoReadings.nanori },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No readings are recorded for this kanji.')).toBeVisible();
	},
};

export const Mobile: Story = {
	args: ManyReadings.args,
	globals: { viewport: { value: 'xs' } },
};
