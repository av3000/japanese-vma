import {
	KANJI_VIEWER_CATALOGUE_INCLUDE,
	getInfiniteKanjisQueryKey,
	type KanjiListFilters,
} from '@/api/kanjis/hooks/useInfiniteKanjis';
import {
	WORD_VIEWER_CATALOGUE_INCLUDE,
	getInfiniteWordsQueryKey,
	type WordListFilters,
} from '@/api/words/hooks/useInfiniteWords';

/**
 * The kanji and words an article's processing attached, read from the kanji and word indexes
 * filtered by `article_uuid` (#268).
 *
 * There is deliberately no article-scoped endpoint for these. Both indexes already answer the
 * question, and they carry the filters, the sorting and the `viewer_catalogue_state` include
 * that an article-scoped route would have had to grow a second time. This module holds the
 * filters in one place so the hooks that read them and the invalidation that refreshes them
 * cannot drift onto different query keys.
 *
 * Both always ask for `viewer_catalogue_state`, as the dictionary lists do: the API ignores it
 * for guests, and one key per article keeps the rail's word count and the tables on one query.
 */

export const ARTICLE_ATTACHMENTS_PAGE_SIZE = 20;

export const articleKanjiFilters = (articleUuid: string): KanjiListFilters => ({
	article_uuid: articleUuid,
	per_page: ARTICLE_ATTACHMENTS_PAGE_SIZE,
	include: KANJI_VIEWER_CATALOGUE_INCLUDE,
});

export const articleWordFilters = (articleUuid: string): WordListFilters => ({
	article_uuid: articleUuid,
	per_page: ARTICLE_ATTACHMENTS_PAGE_SIZE,
	include: WORD_VIEWER_CATALOGUE_INCLUDE,
});

export const articleKanjisQueryKey = (articleUuid: string) =>
	getInfiniteKanjisQueryKey(articleKanjiFilters(articleUuid));

export const articleWordsQueryKey = (articleUuid: string) => getInfiniteWordsQueryKey(articleWordFilters(articleUuid));
