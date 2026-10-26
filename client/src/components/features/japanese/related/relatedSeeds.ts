import { kanjiArticleFilters, wordArticleFilters } from '@/api/articles/related';
import { getCommentsQueryKey } from '@/api/comments';
import { getArticleIndexQueryKey } from '@/api/generated/article/article';
import { getKanjiIndexQueryKey } from '@/api/generated/kanji/kanji';
import { getSentenceIndexQueryKey } from '@/api/generated/sentence/sentence';
import { getWordIndexQueryKey } from '@/api/generated/word/word';
import { kanjiSentenceFilters, kanjiWordFilters } from '@/api/kanjis/related';
import { radicalKanjiFilters } from '@/api/radicals/related';
import { infiniteSeed, pageSeed, type QuerySeed } from '@/storybook/decorators';
import { articleRows, indexPage, personSentenceRows, personWordRows, treeKanjiRows } from './dictionaryDetail.fixtures';

/*
 * Cache seeds for the dictionary detail stories (#352): the first page of each related list, with
 * the full-data totals from the UI-DICT-DETAIL-00 check, or empty.
 */

export const kanjiRelatedSeeds = (
	kanji: { id: number; uuid: string },
	totals: { words: number; sentences: number; articles: number },
): QuerySeed[] => [
	pageSeed(
		getWordIndexQueryKey(kanjiWordFilters(kanji.uuid)),
		indexPage(totals.words > 0 ? personWordRows : [], totals.words),
	),
	pageSeed(
		getSentenceIndexQueryKey(kanjiSentenceFilters(kanji.uuid)),
		indexPage(totals.sentences > 0 ? personSentenceRows : [], totals.sentences),
	),
	pageSeed(
		getArticleIndexQueryKey(kanjiArticleFilters(kanji.id)),
		indexPage(articleRows.slice(0, totals.articles), totals.articles),
	),
];

export const wordRelatedSeeds = (wordId: number, articles: number): QuerySeed[] => [
	pageSeed(getArticleIndexQueryKey(wordArticleFilters(wordId)), indexPage(articleRows.slice(0, articles), articles)),
];

export const radicalKanjiSeeds = (radicalUuid: string, total: number): QuerySeed[] => [
	pageSeed(
		getKanjiIndexQueryKey(radicalKanjiFilters(radicalUuid)),
		indexPage(total > 0 ? treeKanjiRows : [], total, 25),
	),
];

export const sentenceCommentSeeds = (sentenceUuid: string): QuerySeed[] => [
	infiniteSeed(getCommentsQueryKey('sentence', sentenceUuid), [indexPage([], 0)]),
];
