import { useState } from 'react';
import { keepPreviousData, useQuery, type InfiniteData, type QueryKey } from '@tanstack/react-query';
import type { PaginationResource } from '@/api/generated/model/paginationResource';

/** One page of a v1 index: `{ items, pagination }`, the shape every dictionary index returns. */
export interface IndexPage<Row> {
	items: Row[];
	pagination: PaginationResource;
}

/** The largest `per_page` the dictionary indexes accept. */
export const INDEX_MAX_PER_PAGE = 100;

export interface LoadAllProgress {
	loaded: number;
	total: number;
}

/**
 * Every page of an index, fetched one after another at the largest page size the API allows and
 * merged into one page. `onProgress` reports after each page, for a "Loading 200 of 233…" line.
 */
export const fetchAllPages = async <Row>(
	fetchPage: (page: number) => Promise<IndexPage<Row>>,
	onProgress?: (progress: LoadAllProgress) => void,
): Promise<IndexPage<Row>> => {
	const items: Row[] = [];
	let page = 1;
	let last: IndexPage<Row>;

	do {
		last = await fetchPage(page);
		items.push(...last.items);
		onProgress?.({ loaded: items.length, total: last.pagination.total });
		page += 1;
	} while (last.pagination.has_more);

	return {
		items,
		pagination: {
			...last.pagination,
			page: 1,
			per_page: items.length,
			last_page: 1,
			has_more: false,
		},
	};
};

/**
 * Writes a change to one row into every cached list for these filters: the numbered pages, the
 * "all" list and any infinite list, so a Save in one view shows in the others.
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

interface UsePagedIndexOptions<Params extends object, Row> {
	/** The filters without `page`; `per_page` sets the size of a numbered page. */
	filters: Params;
	/** The generated query-key helper for this index, e.g. `getKanjiIndexQueryKey`. */
	queryKey: (params: Params & { page?: number }) => QueryKey;
	/** The generated client call for this index, e.g. `kanjiIndex`. */
	fetchPage: (params: Params & { page: number }, signal?: AbortSignal) => Promise<IndexPage<Row>>;
	enabled?: boolean;
}

/**
 * A dictionary index read a numbered page at a time, with a switch to load every page at once.
 * Keys are the index's own keys with the page added, so invalidating the filters (as processing
 * and item removal do) also refreshes these pages and the "all" list.
 */
export const usePagedIndex = <Params extends object, Row>({
	filters,
	queryKey,
	fetchPage,
	enabled = true,
}: UsePagedIndexOptions<Params, Row>) => {
	const [page, setPage] = useState(1);
	const [isAll, setIsAll] = useState(false);
	const [progress, setProgress] = useState<LoadAllProgress | null>(null);

	const pageQuery = useQuery({
		queryKey: queryKey({ ...filters, page }),
		queryFn: ({ signal }) => fetchPage({ ...filters, page }, signal),
		enabled: enabled && !isAll,
		placeholderData: keepPreviousData,
	});

	const allQuery = useQuery({
		queryKey: [...queryKey(filters), 'all'],
		queryFn: ({ signal }) =>
			fetchAllPages(
				(next) => fetchPage({ ...filters, per_page: INDEX_MAX_PER_PAGE, page: next }, signal),
				setProgress,
			),
		enabled: enabled && isAll,
	});

	const active = isAll ? allQuery : pageQuery;
	const data = active.data;
	const pageCount = isAll ? 1 : (data?.pagination.last_page ?? 1);

	return {
		rows: data?.items ?? [],
		total: data?.pagination.total ?? 0,
		/** Rows per numbered page, as the server applied it. */
		perPage: data?.pagination.per_page ?? 0,
		page,
		pageCount,
		setPage,
		isAll,
		loadAll: () => {
			setProgress(null);
			setIsAll(true);
		},
		showPages: () => setIsAll(false),
		progress: isAll && allQuery.isFetching ? progress : null,
		isPending: active.isPending,
		isFetching: active.isFetching,
		isError: active.isError,
	};
};

export type PagedIndex<Row> = ReturnType<typeof usePagedIndex<object, Row>>;
