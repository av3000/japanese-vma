import type { SentenceResource } from '@/api/generated/model/sentenceResource';
import type { WordResource } from '@/api/generated/model/wordResource';
import { getSentenceIndexQueryKey, sentenceIndex } from '@/api/generated/sentence/sentence';
import { getWordIndexQueryKey, wordIndex } from '@/api/generated/word/word';
import { usePagedIndex } from '@/api/pagedIndex';
import type { SentenceListFilters } from '@/api/sentences/hooks/useInfiniteSentences';
import { WORD_VIEWER_CATALOGUE_INCLUDE, type WordListFilters } from '@/api/words/hooks/useInfiniteWords';

/**
 * The words and sentences written with one kanji, read from the word and sentence indexes filtered
 * by `kanji_uuid` (#352), as article attachments read `article_uuid` (#268). The preview on the
 * kanji page and the modal's first page are the same query.
 */

export const KANJI_RELATED_PAGE_SIZE = 20;

export const kanjiWordFilters = (kanjiUuid: string): WordListFilters => ({
	kanji_uuid: kanjiUuid,
	per_page: KANJI_RELATED_PAGE_SIZE,
	include: WORD_VIEWER_CATALOGUE_INCLUDE,
});

export const kanjiSentenceFilters = (kanjiUuid: string): SentenceListFilters => ({
	kanji_uuid: kanjiUuid,
	per_page: KANJI_RELATED_PAGE_SIZE,
});

/** The words written with a kanji, a numbered page (20) at a time, with viewer save state. */
export const useKanjiWordPages = (kanjiUuid: string) =>
	usePagedIndex<WordListFilters, WordResource>({
		filters: kanjiWordFilters(kanjiUuid),
		queryKey: getWordIndexQueryKey,
		fetchPage: (params, signal) => wordIndex(params, undefined, signal),
		enabled: Boolean(kanjiUuid),
	});

/** The sentences written with a kanji, a numbered page (20) at a time. */
export const useKanjiSentencePages = (kanjiUuid: string) =>
	usePagedIndex<SentenceListFilters, SentenceResource>({
		filters: kanjiSentenceFilters(kanjiUuid),
		queryKey: getSentenceIndexQueryKey,
		fetchPage: (params, signal) => sentenceIndex(params, undefined, signal),
		enabled: Boolean(kanjiUuid),
	});
