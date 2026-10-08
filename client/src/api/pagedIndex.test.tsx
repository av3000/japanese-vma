/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderWithAct } from '@/test/renderWithAct';
import { fetchAllPages, updateCachedRows, usePagedIndex, type IndexPage } from './pagedIndex';

type Row = { id: number; label: string; saved?: boolean };

const rows = (from: number, count: number): Row[] =>
	Array.from({ length: count }, (_, index) => ({ id: from + index, label: `row ${from + index}` }));

/** A fake index of `total` rows that honours `page` and `per_page`. */
const fakeIndex = (total: number) =>
	vi.fn(async ({ page, per_page = 20 }: { page: number; per_page?: number }): Promise<IndexPage<Row>> => {
		const start = (page - 1) * per_page;
		const items = rows(start + 1, Math.max(0, Math.min(per_page, total - start)));

		return {
			items,
			pagination: {
				page,
				per_page,
				total,
				last_page: Math.ceil(total / per_page),
				has_more: start + per_page < total,
			},
		};
	});

describe('fetchAllPages', () => {
	it('fetches page after page until there are no more, reporting progress', async () => {
		const index = fakeIndex(233);
		const progress = vi.fn();

		const all = await fetchAllPages((page) => index({ page, per_page: 100 }), progress);

		expect(index).toHaveBeenCalledTimes(3);
		expect(all.items).toHaveLength(233);
		expect(all.pagination).toMatchObject({ total: 233, has_more: false, last_page: 1 });
		expect(progress.mock.calls.map(([value]) => value)).toEqual([
			{ loaded: 100, total: 233 },
			{ loaded: 200, total: 233 },
			{ loaded: 233, total: 233 },
		]);
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

	it('reads one numbered page at a time, then every page at once', async () => {
		const index = fakeIndex(45);
		const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
		const seen: { list?: ReturnType<typeof usePagedIndex<{ per_page: number }, Row>> } = {};

		const Probe = () => {
			seen.list = usePagedIndex<{ per_page: number }, Row>({
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
		expect(seen.list).toMatchObject({ page: 1, pageCount: 3, total: 45, perPage: 20, isAll: false });

		await view.flush(() => seen.list?.setPage(3));
		await vi.waitFor(() => expect(seen.list?.rows[0].id).toBe(41));
		expect(seen.list?.rows).toHaveLength(5);

		await view.flush(() => seen.list?.loadAll());
		await vi.waitFor(() => expect(seen.list?.rows).toHaveLength(45));
		expect(seen.list).toMatchObject({ isAll: true, pageCount: 1 });
		expect(index).toHaveBeenLastCalledWith({ per_page: 100, page: 1 });

		await view.unmount();
	});
});
