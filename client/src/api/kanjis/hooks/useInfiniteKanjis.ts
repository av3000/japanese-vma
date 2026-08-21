import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { getKanjiIndexQueryKey, kanjiIndex } from '@/api/generated/kanji/kanji';
import type { KanjiIndexQueryError } from '@/api/generated/kanji/kanji';
import type { KanjiIndex200 } from '@/api/generated/model/kanjiIndex200';
import type { KanjiIndexParams } from '@/api/generated/model/kanjiIndexParams';

export type KanjiListFilters = Omit<KanjiIndexParams, 'page'>;
export type KanjiListResponse = KanjiIndex200;
export type KanjiViewerCatalogueState = NonNullable<
	KanjiListResponse['items'][number]['viewer_catalogue_state']
>;

export const KANJI_VIEWER_CATALOGUE_INCLUDE = 'viewer_catalogue_state';

type UseInfiniteKanjisOptions = {
	enabled?: boolean;
	filters?: KanjiListFilters;
};

export const getInfiniteKanjisQueryKey = (filters: KanjiListFilters = {}) => getKanjiIndexQueryKey(filters);

export const getNextKanjisPageParam = (lastPage: KanjiListResponse) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getKanjisTotal = (pages: KanjiListResponse[] | undefined) => pages?.[0]?.pagination.total ?? 0;

export const applyKanjiViewerCatalogueState = (
	data: InfiniteData<KanjiListResponse> | undefined,
	kanjiId: number,
	viewerCatalogueState: KanjiViewerCatalogueState,
): InfiniteData<KanjiListResponse> | undefined => {
	if (!data) {
		return data;
	}

	return {
		...data,
		pages: data.pages.map((page) => ({
			...page,
			items: page.items.map((kanji) =>
				kanji.id === kanjiId
					? { ...kanji, viewer_catalogue_state: viewerCatalogueState }
					: kanji,
			),
		})),
	};
};

export const useInfiniteKanjis = ({ enabled = true, filters = {} }: UseInfiniteKanjisOptions = {}) => {
	const query = useInfiniteQuery<
		KanjiListResponse,
		KanjiIndexQueryError,
		InfiniteData<KanjiListResponse>,
		ReturnType<typeof getInfiniteKanjisQueryKey>,
		number
	>({
		queryKey: getInfiniteKanjisQueryKey(filters),
		queryFn: ({ pageParam, signal }) => kanjiIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextKanjisPageParam,
		enabled,
	});

	const pages = query.data?.pages as KanjiListResponse[] | undefined;
	const kanjis = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getKanjisTotal(pages);

	return {
		...query,
		kanjis,
		total,
	};
};
