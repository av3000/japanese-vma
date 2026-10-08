import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { QueryClient } from '@tanstack/react-query';
import {
	catalogueDestroy,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';
import { createListDeleteMutationOptions } from '@/api/listDelete';

export const createDeleteCatalogueMutationOptions = (
	queryClient: Pick<QueryClient, 'setQueriesData' | 'invalidateQueries' | 'removeQueries'>,
) =>
	createListDeleteMutationOptions<CatalogueListResource>(queryClient, {
		destroy: (uuid) => catalogueDestroy(uuid),
		listsKey: getCatalogueIndexQueryKey(),
		detailKey: (uuid) => getCatalogueShowQueryKey(uuid),
	});

/** Deletes one of the signed-in user's lists from the dashboard. */
export const useDeleteCatalogueMutation = () => {
	const queryClient = useQueryClient();

	return useMutation(createDeleteCatalogueMutationOptions(queryClient));
};
