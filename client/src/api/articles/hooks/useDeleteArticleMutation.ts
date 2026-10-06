import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { QueryClient } from '@tanstack/react-query';
import { articleDestroy } from '@/api/generated/article/article';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { createListDeleteMutationOptions } from '@/api/listDelete';
import { articleKeys } from '../keys';

export const createDeleteArticleMutationOptions = (
	queryClient: Pick<QueryClient, 'setQueriesData' | 'invalidateQueries' | 'removeQueries'>,
) =>
	createListDeleteMutationOptions<ArticleListResource>(queryClient, {
		destroy: (uuid) => articleDestroy(uuid),
		listsKey: articleKeys.lists(),
		detailKey: (uuid) => articleKeys.detail(uuid),
	});

/**
 * Deletes one of the signed-in user's articles. The detail page keeps its own inline mutation
 * for now (`routes/ArticleDetails/ArticleContent`); this is the dashboard's.
 */
export const useDeleteArticleMutation = () => {
	const queryClient = useQueryClient();

	return useMutation(createDeleteArticleMutationOptions(queryClient));
};
