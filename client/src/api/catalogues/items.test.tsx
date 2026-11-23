/**
 * @vitest-environment jsdom
 */
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { catalogueRemoveItem, getCatalogueShowQueryKey } from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { renderWithAct } from '@/test/renderWithAct';
import {
	catalogueItemsQueryKey,
	catalogueKanjiFilters,
	catalogueSentenceFilters,
	useRemoveCatalogueItem,
	withoutCatalogueItem,
} from './items';

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	catalogueRemoveItem: vi.fn(),
	getCatalogueShowQueryKey: (uuid: string) => [`/catalogues/${uuid}`],
}));

const UUID = 'c1c1c1c1-0000-4000-8000-000000000001';

const detail = (items: Array<{ id: number }>, itemsCount = items.length) =>
	({ uuid: UUID, items, items_count: itemsCount }) as unknown as CatalogueDetailResource;

describe('catalogue item filters', () => {
	it('page the dictionary indexes by catalogue, 25 at a time', () => {
		expect(catalogueKanjiFilters(UUID)).toEqual({
			catalogue_uuid: UUID,
			per_page: 25,
			include: 'viewer_catalogue_state',
		});
		expect(catalogueSentenceFilters(UUID)).toEqual({ catalogue_uuid: UUID, per_page: 25 });
	});

	it('have no items query for article catalogues, which read the payload', () => {
		expect(catalogueItemsQueryKey('articles', UUID)).toBeNull();
		expect(catalogueItemsQueryKey('kanji', UUID)).not.toBeNull();
	});
});

describe('withoutCatalogueItem', () => {
	it('drops the item and lowers the count', () => {
		expect(withoutCatalogueItem(detail([{ id: 11 }, { id: 12 }]), 12)).toMatchObject({
			items: [{ id: 11 }],
			items_count: 1,
		});
	});

	it('lowers the count for a dictionary catalogue whose payload no longer lists the item', () => {
		expect(withoutCatalogueItem(detail([], 3), 12)).toMatchObject({ items_count: 3 });
	});

	it('never goes below zero and leaves a missing cache alone', () => {
		expect(withoutCatalogueItem(detail([{ id: 12 }], 0), 12)?.items_count).toBe(0);
		expect(withoutCatalogueItem(undefined, 12)).toBeUndefined();
	});
});

describe('useRemoveCatalogueItem', () => {
	afterEach(() => {
		vi.resetAllMocks();
		document.body.innerHTML = '';
	});

	it('removes through the v1 item endpoint, then updates the detail and refetches the list', async () => {
		vi.mocked(catalogueRemoveItem).mockResolvedValue(204);
		const queryClient = new QueryClient();
		queryClient.setQueryData(getCatalogueShowQueryKey(UUID), detail([{ id: 11 }, { id: 12 }]));
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
		const mutation: { current?: ReturnType<typeof useRemoveCatalogueItem> } = {};

		const Probe = () => {
			mutation.current = useRemoveCatalogueItem(UUID, 'kanji');
			return null;
		};
		const view = await renderWithAct(
			<QueryClientProvider client={queryClient}>
				<Probe />
			</QueryClientProvider>,
		);

		await view.flush(async () => {
			await mutation.current?.mutateAsync(12);
		});

		expect(catalogueRemoveItem).toHaveBeenCalledWith(UUID, 12);
		expect(queryClient.getQueryData(getCatalogueShowQueryKey(UUID))).toMatchObject({
			items: [{ id: 11 }],
			items_count: 1,
		});
		expect(invalidate).toHaveBeenCalledWith({ queryKey: catalogueItemsQueryKey('kanji', UUID) });

		await view.unmount();
	});
});
