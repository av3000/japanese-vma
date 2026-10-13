import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { mapWordDetail } from '@/api/words/details';
import {
	wordKanaOnly,
	wordManyKanji,
	wordManyMeanings,
} from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { wordRelatedSeeds } from '@/components/features/japanese/related/relatedSeeds';
import WordContent from './WordContent';

/** Word detail in the Reading Room layout (UI-DICT-DETAIL, #352), on real dictionary entries. */
const meta = {
	title: 'Pages/WordDetails/Content',
	component: WordContent,
	parameters: { layout: 'fullscreen', seeds: wordRelatedSeeds(wordManyMeanings.id, 2) },
	args: { word: mapWordDetail(wordManyMeanings) },
} satisfies Meta<typeof WordContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** 出る for a guest: 77 glosses in one paragraph. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByText(/to leave; to exit; to go out;/)).toBeVisible();
		await expect(canvas.getByRole('complementary', { name: 'About this word' })).toBeVisible();
	},
};

export const SignedIn: Story = {
	parameters: { auth: 'member' },
};

/** 公共土木施設災害復旧事業費国庫負担法: the longest word title, with 18 kanji. */
export const ManyKanji: Story = {
	args: { word: mapWordDetail(wordManyKanji) },
	parameters: { seeds: wordRelatedSeeds(wordManyKanji.id, 0) },
	play: async ({ canvasElement }) => {
		await expect(
			within(canvasElement).getByRole('heading', { level: 2, name: /Kanji in this word\s*18/ }),
		).toBeVisible();
	},
};

/** どうぞ: written in kana, so no reading line and no kanji. */
export const KanaOnly: Story = {
	args: { word: mapWordDetail(wordKanaOnly) },
	parameters: { seeds: wordRelatedSeeds(wordKanaOnly.id, 1) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('This word is written without kanji.')).toBeVisible();
	},
};
