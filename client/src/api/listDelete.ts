import type { InfiniteData, QueryClient, QueryKey, UseMutationOptions } from '@tanstack/react-query';
import { dashboardKeys } from '@/api/dashboard/keys';

/** Drops one item, by uuid, from every page of a cached infinite list. */
export const removeFromInfinitePages = <Page extends { items: Array<{ uuid: string }> }>(
	data: InfiniteData<Page> | undefined,
	uuid: string,
): InfiniteData<Page> | undefined =>
	data?.pages
		? {
				...data,
				pages: data.pages.map((page) => ({
					...page,
					items: page.items.filter((item) => item.uuid !== uuid),
				})),
			}
		: data;

interface ListDeleteTarget {
	/** The generated destroy call. */
	destroy: (uuid: string) => Promise<unknown>;
	/** Prefix shared by every cached list of this resource, e.g. `articleKeys.lists()`. */
	listsKey: QueryKey;
	/** The cached detail entry of one item. */
	detailKey: (uuid: string) => QueryKey;
}

/**
 * Delete one of the signed-in user's items from a list view. On success the item leaves every
 * cached list page at once, its detail entry is dropped, and the lists and the dashboard counts
 * are refetched so totals come from the server. Shared by the article and list deletes.
 */
export const createListDeleteMutationOptions = <Page extends { items: Array<{ uuid: string }> }>(
	queryClient: Pick<QueryClient, 'setQueriesData' | 'invalidateQueries' | 'removeQueries'>,
	{ destroy, listsKey, detailKey }: ListDeleteTarget,
): UseMutationOptions<unknown, unknown, string> => ({
	mutationFn: (uuid) => destroy(uuid),

	onSuccess: (_data, uuid) => {
		queryClient.setQueriesData<InfiniteData<Page>>({ queryKey: listsKey }, (data) =>
			removeFromInfinitePages(data, uuid),
		);
		queryClient.removeQueries({ queryKey: detailKey(uuid) });
		void queryClient.invalidateQueries({ queryKey: listsKey });
		void queryClient.invalidateQueries({ queryKey: dashboardKeys.all });
	},
});
