import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { getRadicalIndexQueryKey, radicalIndex } from '@/api/generated/radical/radical';
import type { RadicalIndexQueryError } from '@/api/generated/radical/radical';
import type { RadicalIndex200 } from '@/api/generated/model/radicalIndex200';
import type { RadicalIndexParams } from '@/api/generated/model/radicalIndexParams';

export type RadicalListFilters = Omit<RadicalIndexParams, 'page'>;

type UseInfiniteRadicalsOptions = {
	enabled?: boolean;
	filters?: RadicalListFilters;
};

export const getInfiniteRadicalsQueryKey = (filters: RadicalListFilters = {}) => getRadicalIndexQueryKey(filters);

export const getNextRadicalsPageParam = (lastPage: RadicalIndex200) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getRadicalsTotal = (pages: RadicalIndex200[] | undefined) => pages?.[0]?.pagination.total ?? 0;

export const useInfiniteRadicals = ({ enabled = true, filters = {} }: UseInfiniteRadicalsOptions = {}) => {
	const query = useInfiniteQuery<
		RadicalIndex200,
		RadicalIndexQueryError,
		InfiniteData<RadicalIndex200>,
		ReturnType<typeof getInfiniteRadicalsQueryKey>,
		number
	>({
		queryKey: getInfiniteRadicalsQueryKey(filters),
		queryFn: ({ pageParam, signal }) => radicalIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextRadicalsPageParam,
		enabled,
	});

	const radicals = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getRadicalsTotal(query.data?.pages);

	return {
		...query,
		radicals,
		total,
	};
};
