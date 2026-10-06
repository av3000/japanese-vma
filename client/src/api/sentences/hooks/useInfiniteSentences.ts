import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import type { SentenceIndexParams } from '@/api/generated/model/sentenceIndexParams';
import type { SentenceListResource } from '@/api/generated/model/sentenceListResource';
import { getSentenceIndexQueryKey, sentenceIndex } from '@/api/generated/sentence/sentence';
import type { SentenceIndexQueryError } from '@/api/generated/sentence/sentence';

export type SentenceListFilters = Omit<SentenceIndexParams, 'page'>;

type UseInfiniteSentencesOptions = {
	enabled?: boolean;
	filters?: SentenceListFilters;
};

export const getInfiniteSentencesQueryKey = (filters: SentenceListFilters = {}) => getSentenceIndexQueryKey(filters);

export const getNextSentencesPageParam = (lastPage: SentenceListResource) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getSentencesTotal = (pages: SentenceListResource[] | undefined) => pages?.[0]?.pagination.total ?? 0;

export const useInfiniteSentences = ({ enabled = true, filters = {} }: UseInfiniteSentencesOptions = {}) => {
	const query = useInfiniteQuery<
		SentenceListResource,
		SentenceIndexQueryError,
		InfiniteData<SentenceListResource>,
		ReturnType<typeof getInfiniteSentencesQueryKey>,
		number
	>({
		queryKey: getInfiniteSentencesQueryKey(filters),
		queryFn: ({ pageParam, signal }) => sentenceIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextSentencesPageParam,
		enabled,
	});

	const pages = query.data?.pages as SentenceListResource[] | undefined;
	const sentences = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getSentencesTotal(pages);

	return {
		...query,
		sentences,
		total,
	};
};
