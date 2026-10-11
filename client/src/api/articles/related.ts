import { articleIndex, getArticleIndexQueryKey } from '@/api/generated/article/article';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';
import type { ArticleResource } from '@/api/generated/model/articleResource';
import { usePagedIndex } from '@/api/pagedIndex';

/**
 * The articles that use one kanji or word, read from the article index's `kanji_ids[]` and
 * `word_ids[]` filters (#352). The index applies the usual visibility rules, so a guest sees only
 * public articles. The reader's search goes to the index's `q`.
 */

export const RELATED_ARTICLES_PAGE_SIZE = 20;

export type RelatedArticleFilters = Omit<ArticleIndexParams, 'page' | 'q'>;

export const kanjiArticleFilters = (kanjiId: number): RelatedArticleFilters => ({
	'kanji_ids[]': [kanjiId],
	per_page: RELATED_ARTICLES_PAGE_SIZE,
	include_stats_counts: true,
});

export const wordArticleFilters = (wordId: number): RelatedArticleFilters => ({
	'word_ids[]': [wordId],
	per_page: RELATED_ARTICLES_PAGE_SIZE,
	include_stats_counts: true,
});

type PagedArticleParams = RelatedArticleFilters & { page?: number; keyword?: string };

/** `usePagedIndex` adds `keyword`; the article index calls it `q`. */
export const toArticleIndexParams = ({ keyword, ...params }: PagedArticleParams): ArticleIndexParams =>
	keyword ? { ...params, q: keyword } : params;

const useRelatedArticlePages = (filters: RelatedArticleFilters, enabled: boolean) =>
	usePagedIndex<RelatedArticleFilters, ArticleResource>({
		filters,
		queryKey: (params) => getArticleIndexQueryKey(toArticleIndexParams(params)),
		fetchPage: (params, signal) => articleIndex(toArticleIndexParams(params), undefined, signal),
		enabled,
	});

/** The articles that use a kanji, a numbered page (20) at a time. */
export const useKanjiArticlePages = (kanjiId: number | undefined) =>
	useRelatedArticlePages(kanjiArticleFilters(kanjiId ?? 0), kanjiId !== undefined);

/** The articles that use a word, a numbered page (20) at a time. */
export const useWordArticlePages = (wordId: number | undefined) =>
	useRelatedArticlePages(wordArticleFilters(wordId ?? 0), wordId !== undefined);
