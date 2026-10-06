import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { InfiniteData, QueryClient, UseMutationOptions } from '@tanstack/react-query';
import { dashboardKeys } from '@/api/dashboard/keys';
import {
	catalogueDestroy,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';

/** Drops one catalogue from every cached index page, so the row disappears before the refetch. */
export const removeCatalogueFromListPages = (
	data: InfiniteData<CatalogueListResource> | undefined,
	catalogueUuid: string,
): InfiniteData<CatalogueListResource> | undefined =>
	data?.pages
		? {
				...data,
				pages: data.pages.map((page) => ({
					...page,
					items: page.items.filter((item) => item.uuid !== catalogueUuid),
				})),
			}
		: data;

/**
 * Split out from the hook so the cache contract can be exercised against a real `QueryClient`.
 * Mirrors `createDeleteArticleMutationOptions`: drop the row, drop the detail, then refetch the
 * catalogue lists and the dashboard counts.
 */
export const createDeleteCatalogueMutationOptions = (
	queryClient: Pick<QueryClient, 'setQueriesData' | 'invalidateQueries' | 'removeQueries'>,
): UseMutationOptions<unknown, unknown, string> => ({
	mutationFn: (catalogueUuid) => catalogueDestroy(catalogueUuid),

	onSuccess: (_data, catalogueUuid) => {
		queryClient.setQueriesData<InfiniteData<CatalogueListResource>>(
			{ queryKey: getCatalogueIndexQueryKey() },
			(data) => removeCatalogueFromListPages(data, catalogueUuid),
		);
		queryClient.removeQueries({ queryKey: getCatalogueShowQueryKey(catalogueUuid) });
		void queryClient.invalidateQueries({ queryKey: getCatalogueIndexQueryKey() });
		void queryClient.invalidateQueries({ queryKey: dashboardKeys.all });
	},
});

/** Deletes one of the signed-in user's lists from the dashboard. */
export const useDeleteCatalogueMutation = () => {
	const queryClient = useQueryClient();

	return useMutation(createDeleteCatalogueMutationOptions(queryClient));
};
