import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import {
	catalogueIndex,
	getCatalogueIndexQueryKey,
	type CatalogueIndexQueryError,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';
import type { FetchCataloguesFilters } from '../catalogues';

export const getInfiniteCataloguesQueryKey = (filters: FetchCataloguesFilters = {}) => getCatalogueIndexQueryKey(filters);

export const getNextCataloguesPageParam = (lastPage: CatalogueListResource) => {
	return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
};

export const getCataloguesTotal = (pages: CatalogueListResource[]) => {
	return pages[0]?.pagination.total ?? 0;
};

interface UseInfiniteCataloguesOptions {
	filters: FetchCataloguesFilters;
	enabled?: boolean;
}

export const useInfiniteCatalogues = ({ filters, enabled = true }: UseInfiniteCataloguesOptions) => {
	const query = useInfiniteQuery<
		CatalogueListResource,
		CatalogueIndexQueryError,
		InfiniteData<CatalogueListResource>,
		ReturnType<typeof getInfiniteCataloguesQueryKey>,
		number
	>({
		queryKey: getInfiniteCataloguesQueryKey(filters),
		queryFn: ({ pageParam, signal }) => catalogueIndex({ ...filters, page: Number(pageParam) }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextCataloguesPageParam,
		enabled,
	});

	const catalogues = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getCataloguesTotal(query.data?.pages ?? []);

	return {
		...query,
		catalogues,
		total,
	};
};
