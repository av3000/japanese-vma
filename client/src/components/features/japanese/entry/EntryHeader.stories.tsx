import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { kanjiNoMeaning, kanjiPerson, sentenceLong, wordKanaOnly } from '../related/dictionaryDetail.fixtures';
import { EntryHeader } from './EntryHeader';

const meta = {
	title: 'Features/Japanese/Entry/EntryHeader',
	component: EntryHeader,
	parameters: { layout: 'padded' },
	args: {
		backTo: '/kanjis',
		backLabel: 'Kanji',
		kind: 'glyph',
		title: kanjiPerson.character,
		lead: kanjiPerson.meanings.join(', '),
	},
} satisfies Meta<typeof EntryHeader>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A kanji: the glyph at display size as the page's `h1`, its meaning under it, a way back to the list. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: '人' })).toHaveAttribute('lang', 'ja');
		await expect(canvas.getByRole('link', { name: /Kanji/ })).toHaveAttribute('href', '/kanjis');
		await expect(canvas.getByText('person')).toBeVisible();
	},
};

/** A word with its kana reading under it. */
export const WordWithReading: Story = {
	args: {
		backTo: '/words',
		backLabel: 'Words',
		kind: 'word',
		title: '出る',
		reading: 'でる',
		lead: 'to leave, to exit, to go out',
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: '出る' })).toBeVisible();
		await expect(canvas.getByText('でる')).toHaveAttribute('lang', 'ja');
	},
};

/** A kana-only word has no reading of its own, so nothing repeats under the title. */
export const KanaOnly: Story = {
	args: {
		backTo: '/words',
		backLabel: 'Words',
		kind: 'word',
		title: wordKanaOnly.word,
		reading: null,
		lead: wordKanaOnly.meaning,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'どうぞ' })).toBeVisible();
		await expect(canvasElement.querySelectorAll('p[lang="ja"]')).toHaveLength(0);
	},
};

/** No meaning recorded: `lead: null` says so, where leaving `lead` out shows no line at all. */
export const MissingMeaning: Story = {
	args: { title: kanjiNoMeaning.character, lead: null },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No English meaning recorded.')).toBeVisible();
	},
};

/** No `lead` at all, for a page that shows its own line below. */
export const WithoutLead: Story = {
	args: { lead: undefined },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByText('No English meaning recorded.')).not.toBeInTheDocument();
	},
};

/** Hostile: a 302-character sentence at reading size, with its source line as children. */
export const LongSentence: Story = {
	args: {
		backTo: '/sentences',
		backLabel: 'Sentences',
		kind: 'sentence',
		title: sentenceLong.content,
		lead: 'We now have the chance to answer that question. This moment is ours.',
		children: <p>From Tatoeba #{sentenceLong.tatoeba_entry}</p>,
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('heading', { level: 1 })).toHaveTextContent(
			sentenceLong.content.slice(0, 20),
		);
	},
};

export const Mobile: Story = {
	args: LongSentence.args,
	globals: { viewport: { value: 'xs' } },
};
