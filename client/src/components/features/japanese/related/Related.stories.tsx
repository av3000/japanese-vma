import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, waitFor, within } from '@storybook/test';
import { kanjiArticleFilters, wordArticleFilters } from '@/api/articles/related';
import { getArticleIndexQueryKey } from '@/api/generated/article/article';
import { getKanjiIndexQueryKey } from '@/api/generated/kanji/kanji';
import { getSentenceIndexQueryKey } from '@/api/generated/sentence/sentence';
import { getWordIndexQueryKey } from '@/api/generated/word/word';
import { kanjiSentenceFilters, kanjiWordFilters } from '@/api/kanjis/related';
import { radicalKanjiFilters } from '@/api/radicals/related';
import { pageSeed, SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import { EntryKanji, KanjiRelated, RadicalKanji, WordRelated } from './';
import {
	articleRows,
	indexPage,
	kanjiNoMeaning,
	kanjiPerson,
	personSentenceRows,
	personWordRows,
	radicalTree,
	sentenceLong,
	treeKanjiRows,
	wordKanaOnly,
	wordManyKanji,
} from './dictionaryDetail.fixtures';

/*
 * The related material under each dictionary entry (#352), on seeded index pages: the totals are the
 * full-data counts from the UI-DICT-DETAIL-00 check (人 2,398 words, 49,419 sentences; 木 674 kanji).
 */

const kanjiSeeds = (kanji: { id: number; uuid: string }, full: boolean): QuerySeed[] => [
	pageSeed(
		getWordIndexQueryKey(kanjiWordFilters(kanji.uuid)),
		full ? indexPage(personWordRows, 2398) : indexPage([], 0),
	),
	pageSeed(
		getSentenceIndexQueryKey(kanjiSentenceFilters(kanji.uuid)),
		full ? indexPage(personSentenceRows, 49419) : indexPage([], 0),
	),
	pageSeed(
		getArticleIndexQueryKey(kanjiArticleFilters(kanji.id)),
		full ? indexPage(articleRows, articleRows.length) : indexPage([], 0),
	),
];

const meta = {
	title: 'Features/Japanese/Related',
	parameters: { layout: 'padded' },
} satisfies Meta;

export default meta;

type Story = StoryObj<typeof meta>;

/** 人, signed in: five words with Save, five sentences, the articles, and "See all" on each. */
export const KanjiThousands: Story = {
	render: () => (
		<SeededQueryClient seeds={kanjiSeeds(kanjiPerson, true)}>
			<KanjiRelated kanji={kanjiPerson} showSave />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('heading', { level: 2, name: /Words with 人\s*2,398/ })).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 2, name: /Sentences with 人\s*49,419/ })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'See all 2,398 words' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'See all 49,419 sentences' })).toBeVisible();
		await expect(within(canvas.getByRole('table', { name: 'Words' })).getAllByRole('row')).toHaveLength(6);
	},
};

/** The words modal: page 1 of 120 with search and page size; it opens on the cached first page. */
export const KanjiWordsModal: Story = {
	render: KanjiThousands.render,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(await canvas.findByRole('button', { name: 'See all 2,398 words' }));
		const dialog = within(await within(document.body).findByRole('dialog', { name: 'Words with 人' }));

		// The dialog fades in; visibility follows its opening transition.
		await waitFor(() => expect(dialog.getByRole('search', { name: 'Search words' })).toBeVisible());
		await expect(within(dialog.getByRole('table', { name: 'Words' })).getAllByRole('row')).toHaveLength(21);
		await expect(dialog.getByRole('navigation', { name: 'Word pages' })).toBeVisible();
	},
};

export const KanjiGuest: Story = {
	render: () => (
		<SeededQueryClient seeds={kanjiSeeds(kanjiPerson, true)}>
			<KanjiRelated kanji={kanjiPerson} showSave={false} />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByRole('table', { name: 'Words' });
		await expect(canvas.queryByRole('button', { name: /^Save / })).toBeNull();
	},
};

/** 夳: no words, sentences or articles. Each heading stays, with one line. */
export const KanjiNothingRelated: Story = {
	render: () => (
		<SeededQueryClient seeds={kanjiSeeds(kanjiNoMeaning, false)}>
			<KanjiRelated kanji={kanjiNoMeaning} showSave />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('No words use this kanji yet.')).toBeVisible();
		await expect(canvas.getByText('No sentences use this kanji yet.')).toBeVisible();
		await expect(canvas.getByText('No articles use this kanji yet.')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: /See all/ })).toBeNull();
	},
};

/** The 18-kanji word, in the order the kanji appear in it, and no articles. */
export const WordManyKanji: Story = {
	render: () => (
		<SeededQueryClient
			seeds={[pageSeed(getArticleIndexQueryKey(wordArticleFilters(wordManyKanji.id)), indexPage([], 0))]}
		>
			<WordRelated word={{ ...wordManyKanji, kanjis: wordManyKanji.kanjis ?? [] }} />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const table = within(canvas.getByRole('table', { name: 'Kanji' }));
		const glyphs = table.getAllByRole('rowheader').map((cell) => cell.textContent);

		await expect(glyphs.slice(0, 4)).toEqual(['公', '共', '土', '木']);
		await expect(await canvas.findByText('No articles use this word yet.')).toBeVisible();
	},
};

export const WordKanaOnly: Story = {
	render: () => (
		<SeededQueryClient
			seeds={[
				pageSeed(
					getArticleIndexQueryKey(wordArticleFilters(wordKanaOnly.id)),
					indexPage(articleRows, articleRows.length),
				),
			]}
		>
			<WordRelated word={{ ...wordKanaOnly, kanjis: [] }} />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('This word is written without kanji.')).toBeVisible();
	},
};

/** 木, signed in: page 1 of 27 inline, with search, page size and Save. */
export const RadicalThousands: Story = {
	render: () => (
		<SeededQueryClient
			seeds={[
				pageSeed(
					getKanjiIndexQueryKey(radicalKanjiFilters(radicalTree.uuid)),
					indexPage(treeKanjiRows, 674, 25),
				),
			]}
		>
			<RadicalKanji radicalUuid={radicalTree.uuid} showSave />
		</SeededQueryClient>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', { level: 2, name: /Kanji with this radical\s*674/ }),
		).toBeVisible();
		await expect(within(canvas.getByRole('table', { name: 'Kanji' })).getAllByRole('row')).toHaveLength(26);
		await expect(canvas.getByRole('navigation', { name: 'Kanji pages' })).toBeVisible();
	},
};

/** The 302-character sentence's 59 kanji, in reading order, with no Save column. */
export const SentenceManyKanji: Story = {
	render: () => (
		<EntryKanji
			title="Kanji in this sentence"
			text={sentenceLong.content}
			kanjis={sentenceLong.kanjis ?? []}
			emptyMessage="This sentence is written without kanji."
		/>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 2, name: /Kanji in this sentence\s*59/ })).toBeVisible();
		await expect(canvas.queryByRole('button', { name: /^Save / })).toBeNull();
	},
};
