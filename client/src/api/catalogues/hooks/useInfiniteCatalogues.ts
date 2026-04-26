import { useInfiniteQuery } from '@tanstack/react-query';
import { fetchCatalogues, type CataloguesResponse, type FetchCataloguesFilters } from '../catalogues';

export const getNextCataloguesPageParam = (lastPage: CataloguesResponse) => {
	return lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;
};

export const getCataloguesTotal = (pages: CataloguesResponse[]) => {
	return pages[0]?.pagination.total ?? 0;
};

interface UseInfiniteCataloguesOptions {
	filters: FetchCataloguesFilters;
	enabled?: boolean;
}

export const useInfiniteCatalogues = ({ filters, enabled = true }: UseInfiniteCataloguesOptions) => {
	const query = useInfiniteQuery({
		queryKey: ['catalogues', filters],
		queryFn: ({ pageParam }) => fetchCatalogues(filters, Number(pageParam)),
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
