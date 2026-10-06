import { QueryClient, type InfiniteData } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import { dashboardKeys } from '@/api/dashboard/keys';
import {
	catalogueDestroy,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';
import { makeDashboardCatalogue } from '@/components/features/dashboard/dashboardListFixtures';
import { createDeleteCatalogueMutationOptions } from './useDeleteCatalogueMutation';

vi.mock('@/api/generated/catalogue/catalogue', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/catalogue/catalogue')>(
		'@/api/generated/catalogue/catalogue',
	);
	return { ...actual, catalogueDestroy: vi.fn() };
});

const page = (uuids: string[]): CatalogueListResource =>
	({
		items: uuids.map((uuid) => makeDashboardCatalogue({ uuid })),
		pagination: { page: 1, per_page: 25, total: uuids.length, last_page: 1, has_more: false },
	}) as CatalogueListResource;

const cache = (...pages: string[][]): InfiniteData<CatalogueListResource> => ({
	pages: pages.map(page),
	pageParams: pages.map((_, index) => index + 1),
});

const uuidsOf = (data: InfiniteData<CatalogueListResource> | undefined) =>
	data?.pages.map((p) => p.items.map((item) => item.uuid));

describe('createDeleteCatalogueMutationOptions', () => {
	it('calls the generated destroy with the uuid', async () => {
		vi.mocked(catalogueDestroy).mockResolvedValue('' as never);

		await createDeleteCatalogueMutationOptions(new QueryClient()).mutationFn?.('list-uuid', undefined as never);

		expect(catalogueDestroy).toHaveBeenCalledWith('list-uuid');
	});

	it('removes the row, drops the detail and refetches lists and counts', () => {
		const queryClient = new QueryClient();
		const listKey = getCatalogueIndexQueryKey({ owner_uid: 'owner', per_page: 25 });
		queryClient.setQueryData(listKey, cache(['a', 'gone']));
		queryClient.setQueryData(getCatalogueShowQueryKey('gone'), { uuid: 'gone' });
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');

		createDeleteCatalogueMutationOptions(queryClient).onSuccess?.(undefined, 'gone', undefined, undefined as never);

		expect(uuidsOf(queryClient.getQueryData(listKey))).toEqual([['a']]);
		expect(queryClient.getQueryData(getCatalogueShowQueryKey('gone'))).toBeUndefined();
		expect(invalidate).toHaveBeenCalledWith({ queryKey: getCatalogueIndexQueryKey() });
		expect(invalidate).toHaveBeenCalledWith({ queryKey: dashboardKeys.all });
	});
});
