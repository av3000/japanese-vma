import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { mapSentenceDetail } from '@/api/sentences/details';
import { sentenceLong, sentenceShort } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { sentenceCommentSeeds } from '@/components/features/japanese/related/relatedSeeds';
import { users } from '@/storybook/fixtures/users';
import SentenceContent from './SentenceContent';

const OWNER_ID = 5;
const memberSentence = mapSentenceDetail({ ...sentenceShort, user_id: OWNER_ID, tatoeba_entry: null });

/** Sentence detail in the Reading Room layout (UI-DICT-DETAIL, #352), on real sentences. */
const meta = {
	title: 'Pages/SentenceDetails',
	component: SentenceContent,
	parameters: { layout: 'fullscreen', seeds: sentenceCommentSeeds(sentenceShort.uuid) },
	args: { sentence: mapSentenceDetail(sentenceShort) },
} satisfies Meta<typeof SentenceContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A Tatoeba sentence for a guest: its kanji, then comments. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('link', { name: /Tatoeba #4777/ })).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 2, name: 'Comments' })).toBeVisible();
	},
};

export const SignedIn: Story = {
	parameters: { auth: 'member' },
};

/** The author of a member sentence: Edit and Delete under "Your sentence". */
export const Owner: Story = {
	args: { sentence: memberSentence },
	parameters: { auth: { user: { ...users.member, id: OWNER_ID } } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 2, name: 'Your sentence' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: /Edit sentence/ })).toBeVisible();
		await expect(canvas.getByRole('button', { name: /Delete sentence/ })).toBeVisible();
		await expect(canvas.getByText('Written by you')).toBeVisible();
	},
};

/** The longest sentence: 302 characters and 59 kanji. */
export const Longest: Story = {
	args: { sentence: mapSentenceDetail(sentenceLong) },
	parameters: { seeds: sentenceCommentSeeds(sentenceLong.uuid) },
	play: async ({ canvasElement }) => {
		await expect(
			within(canvasElement).getByRole('heading', { level: 2, name: /Kanji in this sentence\s*59/ }),
		).toBeVisible();
	},
};
