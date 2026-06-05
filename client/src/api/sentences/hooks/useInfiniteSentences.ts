import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import {
	getSentenceIndexQueryKey,
	sentenceIndex,
} from '@/api/generated/sentence/sentence';
import type { SentenceIndexQueryError } from '@/api/generated/sentence/sentence';
import type { SentenceIndex200 } from '@/api/generated/model/sentenceIndex200';
import type { SentenceIndexParams } from '@/api/generated/model/sentenceIndexParams';

export type SentenceListFilters = Omit<SentenceIndexParams, 'page'>;
export type SentenceListResponse = SentenceIndex200;

type UseInfiniteSentencesOptions = {
	enabled?: boolean;
	filters?: SentenceListFilters;
};

export const getInfiniteSentencesQueryKey = (filters: SentenceListFilters = {}) =>
	getSentenceIndexQueryKey(filters);

export const getNextSentencesPageParam = (lastPage: SentenceListResponse) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getSentencesTotal = (pages: SentenceListResponse[] | undefined) =>
	pages?.[0]?.pagination.total ?? 0;

export const useInfiniteSentences = ({
	enabled = true,
	filters = {},
}: UseInfiniteSentencesOptions = {}) => {
	const query = useInfiniteQuery<
		SentenceListResponse,
		SentenceIndexQueryError,
		InfiniteData<SentenceListResponse>,
		ReturnType<typeof getInfiniteSentencesQueryKey>,
		number
	>({
		queryKey: getInfiniteSentencesQueryKey(filters),
		queryFn: ({ pageParam, signal }) =>
			sentenceIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextSentencesPageParam,
		enabled,
	});

	const pages = query.data?.pages as SentenceListResponse[] | undefined;
	const sentences = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getSentencesTotal(pages);

	return {
		...query,
		sentences,
		total,
	};
};
