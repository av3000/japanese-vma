import { useState } from 'react';
import { keepPreviousData, useQuery, type InfiniteData, type QueryKey } from '@tanstack/react-query';
import type { PaginationResource } from '@/api/generated/model/paginationResource';

/** One page of a v1 index: `{ items, pagination }`, the shape every dictionary index returns. */
export interface IndexPage<Row> {
	items: Row[];
	pagination: PaginationResource;
}

/** The page sizes a reader can pick, starting from the list's own default. The API caps at 100. */
export const pageSizeOptions = (defaultSize: number): number[] =>
	[...new Set([defaultSize, 50, 100])].filter((size) => size <= 100).sort((a, b) => a - b);

/**
 * Writes a change to one row into every cached list for these filters (every page, page size and
 * search, and any infinite list), so a Save in one view shows in the others.
 */
export const updateCachedRows = <Row extends { id: number }>(
	data: unknown,
	rowId: number,
	update: (row: Row) => Row,
): unknown => {
	const mapPage = (page: IndexPage<Row>): IndexPage<Row> => ({
		...page,
		items: page.items.map((row) => (row.id === rowId ? update(row) : row)),
	});

	if (!data || typeof data !== 'object') return data;
	if ('pages' in data) {
		const infinite = data as InfiniteData<IndexPage<Row>>;
		return { ...infinite, pages: infinite.pages.map(mapPage) };
	}
	if ('items' in data) return mapPage(data as IndexPage<Row>);

	return data;
};

interface PagedParams {
	page: number;
	per_page: number;
	keyword?: string;
}

interface UsePagedIndexOptions<Params extends object, Row> {
	/** The fixed filters, e.g. `{ article_uuid }`; `per_page` in them is the default page size. */
	filters: Params;
	/** The generated query-key helper for this index, e.g. `getKanjiIndexQueryKey`. */
	queryKey: (params: Params & Partial<PagedParams>) => QueryKey;
	/** The generated client call for this index, e.g. `kanjiIndex`. */
	fetchPage: (params: Params & PagedParams, signal?: AbortSignal) => Promise<IndexPage<Row>>;
	enabled?: boolean;
}

/**
 * A dictionary index read one numbered page at a time, with a page size and a keyword search the
 * reader can change. Changing either goes back to page 1. Keys are the index's own keys with these
 * params added, so invalidating the fixed filters (as processing and item removal do) refreshes
 * every page, size and search.
 */
export const usePagedIndex = <Params extends object, Row>({
	filters,
	queryKey,
	fetchPage,
	enabled = true,
}: UsePagedIndexOptions<Params, Row>) => {
	const defaultPerPage = (filters as { per_page?: number | null }).per_page ?? 20;
	const [page, setPage] = useState(1);
	const [perPage, setPerPageState] = useState(defaultPerPage);
	const [keyword, setKeywordState] = useState('');

	const params = { ...filters, per_page: perPage, page, ...(keyword ? { keyword } : {}) };

	const query = useQuery({
		queryKey: queryKey(params),
		queryFn: ({ signal }) => fetchPage(params, signal),
		enabled,
		placeholderData: keepPreviousData,
	});

	const data = query.data;

	return {
		rows: data?.items ?? [],
		total: data?.pagination.total ?? 0,
		page,
		pageCount: data?.pagination.last_page ?? 1,
		setPage,
		perPage,
		pageSizes: pageSizeOptions(defaultPerPage),
		setPerPage: (next: number) => {
			setPerPageState(next);
			setPage(1);
		},
		keyword,
		setKeyword: (next: string) => {
			setKeywordState(next.trim());
			setPage(1);
		},
		isPending: query.isPending,
		isFetching: query.isFetching,
		isError: query.isError,
	};
};

export type PagedIndex<Row> = ReturnType<typeof usePagedIndex<object, Row>>;
