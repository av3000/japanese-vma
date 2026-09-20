import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import {
	articleKanjis,
	articleWords,
	type ArticleKanjisQueryError,
	type ArticleWordsQueryError,
} from '@/api/generated/article/article';
import type { ArticleKanjiListResource } from '@/api/generated/model/articleKanjiListResource';
import type { ArticleWordListResource } from '@/api/generated/model/articleWordListResource';
import { articleKeys } from '../keys';

/**
 * The kanji and words attached to an article, a page at a time (#268).
 *
 * The detail response used to embed both lists in full and the processing subscription
 * refetched the detail on every terminal event, so finishing an article meant re-downloading
 * every word it had just produced. These read from `articles/{uuid}/kanjis` and
 * `articles/{uuid}/words`, which page like every other v1 list.
 */

export const ARTICLE_ATTACHMENTS_PAGE_SIZE = 20;

type UseArticleAttachmentsOptions = {
	enabled?: boolean;
	perPage?: number;
};

const nextPage = (lastPage: { pagination: { has_more: boolean; page: number } }) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const useInfiniteArticleKanjis = (
	articleUuid: string,
	{ enabled = true, perPage = ARTICLE_ATTACHMENTS_PAGE_SIZE }: UseArticleAttachmentsOptions = {},
) => {
	const query = useInfiniteQuery<
		ArticleKanjiListResource,
		ArticleKanjisQueryError,
		InfiniteData<ArticleKanjiListResource>,
		ReturnType<typeof articleKeys.kanjis>,
		number
	>({
		queryKey: articleKeys.kanjis(articleUuid, { per_page: perPage }),
		queryFn: ({ pageParam, signal }) =>
			articleKanjis(articleUuid, { page: pageParam, per_page: perPage }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: nextPage,
		enabled: enabled && Boolean(articleUuid),
	});

	return {
		...query,
		kanjis: query.data?.pages.flatMap((page) => page.items) ?? [],
		total: query.data?.pages[0]?.pagination.total ?? 0,
	};
};

export const useInfiniteArticleWords = (
	articleUuid: string,
	{ enabled = true, perPage = ARTICLE_ATTACHMENTS_PAGE_SIZE }: UseArticleAttachmentsOptions = {},
) => {
	const query = useInfiniteQuery<
		ArticleWordListResource,
		ArticleWordsQueryError,
		InfiniteData<ArticleWordListResource>,
		ReturnType<typeof articleKeys.words>,
		number
	>({
		queryKey: articleKeys.words(articleUuid, { per_page: perPage }),
		queryFn: ({ pageParam, signal }) =>
			articleWords(articleUuid, { page: pageParam, per_page: perPage }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: nextPage,
		enabled: enabled && Boolean(articleUuid),
	});

	return {
		...query,
		words: query.data?.pages.flatMap((page) => page.items) ?? [],
		total: query.data?.pages[0]?.pagination.total ?? 0,
	};
};
