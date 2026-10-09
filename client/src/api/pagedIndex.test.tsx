/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderWithAct } from '@/test/renderWithAct';
import { pageSizeOptions, updateCachedRows, usePagedIndex, type IndexPage } from './pagedIndex';

type Row = { id: number; label: string; saved?: boolean };
type Params = { per_page?: number; page?: number; keyword?: string };

const rows = (from: number, count: number): Row[] =>
	Array.from({ length: count }, (_, index) => ({ id: from + index, label: `row ${from + index}` }));

/** A fake index of `total` rows that honours `page`, `per_page` and a keyword (which matches 3 rows). */
const fakeIndex = (total: number) =>
	vi.fn(async ({ page = 1, per_page = 20, keyword }: Params): Promise<IndexPage<Row>> => {
		const size = keyword ? 3 : total;
		const start = (page - 1) * per_page;
		const items = rows(start + 1, Math.max(0, Math.min(per_page, size - start)));

		return {
			items,
			pagination: {
				page,
				per_page,
				total: size,
				last_page: Math.ceil(size / per_page) || 1,
				has_more: start + per_page < size,
			},
		};
	});

describe('pageSizeOptions', () => {
	it('starts from the list default and stays within the API cap', () => {
		expect(pageSizeOptions(20)).toEqual([20, 50, 100]);
		expect(pageSizeOptions(25)).toEqual([25, 50, 100]);
		expect(pageSizeOptions(50)).toEqual([50, 100]);
	});
});

describe('updateCachedRows', () => {
	const mark = (row: Row) => ({ ...row, saved: true });

	it('updates the row in a page and in every page of an infinite list', () => {
		const page: IndexPage<Row> = {
			items: rows(1, 3),
			pagination: { page: 1, per_page: 3, total: 3, last_page: 1, has_more: false },
		};

		expect((updateCachedRows(page, 2, mark) as IndexPage<Row>).items[1].saved).toBe(true);
		expect(
			(updateCachedRows({ pages: [page], pageParams: [1] }, 2, mark) as { pages: IndexPage<Row>[] }).pages[0]
				.items[1].saved,
		).toBe(true);
		expect(updateCachedRows(undefined, 2, mark)).toBeUndefined();
	});
});

describe('usePagedIndex', () => {
	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('pages, changes the page size and searches, going back to page 1 each time', async () => {
		const index = fakeIndex(45);
		const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
		const seen: { list?: ReturnType<typeof usePagedIndex<Params, Row>> } = {};

		const Probe = () => {
			seen.list = usePagedIndex<Params, Row>({
				filters: { per_page: 20 },
				queryKey: (params) => ['/rows', params],
				fetchPage: (params) => index(params),
			});
			return null;
		};

		const view = await renderWithAct(
			<QueryClientProvider client={queryClient}>
				<Probe />
			</QueryClientProvider>,
		);

		await vi.waitFor(() => expect(seen.list?.rows).toHaveLength(20));
		expect(seen.list).toMatchObject({ page: 1, pageCount: 3, total: 45, perPage: 20, keyword: '' });

		await view.flush(() => seen.list?.setPage(3));
		await vi.waitFor(() => expect(seen.list?.rows[0].id).toBe(41));
		expect(seen.list?.rows).toHaveLength(5);

		await view.flush(() => seen.list?.setPerPage(50));
		await vi.waitFor(() => expect(seen.list?.rows).toHaveLength(45));
		expect(seen.list).toMatchObject({ page: 1, perPage: 50, pageCount: 1 });
		expect(index).toHaveBeenLastCalledWith({ per_page: 50, page: 1 });

		await view.flush(() => seen.list?.setKeyword('  water  '));
		await vi.waitFor(() => expect(seen.list?.total).toBe(3));
		expect(seen.list?.keyword).toBe('water');
		expect(index).toHaveBeenLastCalledWith({ per_page: 50, page: 1, keyword: 'water' });

		await view.unmount();
	});
});
