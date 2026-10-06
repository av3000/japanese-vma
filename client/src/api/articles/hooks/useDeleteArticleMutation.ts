import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { InfiniteData, QueryClient, UseMutationOptions } from '@tanstack/react-query';
import { dashboardKeys } from '@/api/dashboard/keys';
import { articleDestroy } from '@/api/generated/article/article';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';
import { articleKeys } from '../keys';

/** Drops one article from every cached list page, so the row disappears before the refetch lands. */
export const removeArticleFromListPages = (
	data: InfiniteData<ArticleListResource> | undefined,
	articleUuid: string,
): InfiniteData<ArticleListResource> | undefined =>
	data?.pages
		? {
				...data,
				pages: data.pages.map((page) => ({
					...page,
					items: page.items.filter((item) => item.uuid !== articleUuid),
				})),
			}
		: data;

/**
 * Split out from the hook so the cache contract can be exercised against a real `QueryClient`.
 * On success the article leaves every list page at once; the lists and the dashboard counts are
 * then refetched so totals come from the server, and the detail entry is dropped.
 */
export const createDeleteArticleMutationOptions = (
	queryClient: Pick<QueryClient, 'setQueriesData' | 'invalidateQueries' | 'removeQueries'>,
): UseMutationOptions<unknown, unknown, string> => ({
	mutationFn: (articleUuid) => articleDestroy(articleUuid),

	onSuccess: (_data, articleUuid) => {
		queryClient.setQueriesData<InfiniteData<ArticleListResource>>({ queryKey: articleKeys.lists() }, (data) =>
			removeArticleFromListPages(data, articleUuid),
		);
		queryClient.removeQueries({ queryKey: articleKeys.detail(articleUuid) });
		void queryClient.invalidateQueries({ queryKey: articleKeys.lists() });
		void queryClient.invalidateQueries({ queryKey: dashboardKeys.all });
	},
});

/**
 * Deletes one of the signed-in user's articles. The detail page keeps its own inline mutation
 * for now (`routes/ArticleDetails/ArticleContent`); this is the dashboard's.
 */
export const useDeleteArticleMutation = () => {
	const queryClient = useQueryClient();

	return useMutation(createDeleteArticleMutationOptions(queryClient));
};
