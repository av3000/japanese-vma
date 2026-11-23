import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import type { ViewerCatalogueStateResource } from '@/api/generated/model/viewerCatalogueStateResource';
import type { WordIndexParams } from '@/api/generated/model/wordIndexParams';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import { getWordIndexQueryKey, wordIndex } from '@/api/generated/word/word';
import type { WordIndexQueryError } from '@/api/generated/word/word';

export type WordListFilters = Omit<WordIndexParams, 'page'>;

export const WORD_VIEWER_CATALOGUE_INCLUDE = 'viewer_catalogue_state';

type UseInfiniteWordsOptions = {
	enabled?: boolean;
	filters?: WordListFilters;
};

export const getInfiniteWordsQueryKey = (filters: WordListFilters = {}) => getWordIndexQueryKey(filters);

export const getNextWordsPageParam = (lastPage: WordListResource) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getWordsTotal = (pages: WordListResource[] | undefined) => pages?.[0]?.pagination.total ?? 0;

export const applyWordViewerCatalogueState = (
	data: InfiniteData<WordListResource> | undefined,
	wordId: number,
	viewerCatalogueState: ViewerCatalogueStateResource,
): InfiniteData<WordListResource> | undefined => {
	if (!data) {
		return data;
	}

	return {
		...data,
		pages: data.pages.map((page) => ({
			...page,
			items: page.items.map((word) =>
				word.id === wordId
					? {
							...word,
							viewer_catalogue_state: viewerCatalogueState,
						}
					: word,
			),
		})),
	};
};

export const useInfiniteWords = ({ enabled = true, filters = {} }: UseInfiniteWordsOptions = {}) => {
	const query = useInfiniteQuery<
		WordListResource,
		WordIndexQueryError,
		InfiniteData<WordListResource>,
		ReturnType<typeof getInfiniteWordsQueryKey>,
		number
	>({
		queryKey: getInfiniteWordsQueryKey(filters),
		queryFn: ({ pageParam, signal }) => wordIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextWordsPageParam,
		enabled,
	});

	const pages = query.data?.pages as WordListResource[] | undefined;
	const words = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getWordsTotal(pages);

	return {
		...query,
		words,
		total,
	};
};
