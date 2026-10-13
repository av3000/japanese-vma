import * as React from 'react';
import type { Decorator } from '@storybook/react-vite';
import { QueryClient, QueryClientProvider, type QueryKey } from '@tanstack/react-query';

/** One cached query: its key and the data a story wants it to hold. */
export interface QuerySeed {
	queryKey: QueryKey;
	data: unknown;
}

/** An infinite-query cache entry holding the given pages, as `useInfiniteQuery` stores them. */
export const infiniteSeed = (queryKey: QueryKey, pages: unknown[]): QuerySeed => ({
	queryKey,
	data: { pages, pageParams: pages.map((_, index) => index + 1) },
});

/**
 * Page `page` of a paged index list (`usePagedIndex`), from the list's own key: the generated
 * index keys are `[url, params]`, and a numbered page adds `page` to the params.
 */
export const pageSeed = (listKey: QueryKey, data: unknown, page = 1): QuerySeed => {
	const [url, params] = listKey as [unknown, Record<string, unknown> | undefined];

	return { queryKey: [url, { ...(params ?? {}), page }], data };
};

/**
 * A fresh client per story, its cache already holding `seeds`. Queries never go stale, retry or
 * refetch on focus, so a seeded story fetches nothing unless the story itself asks for new data.
 */
const SeededQueryClient: React.FC<{ seeds: readonly QuerySeed[]; children: React.ReactNode }> = ({
	seeds,
	children,
}) => {
	const [client] = React.useState(() => {
		const queryClient = new QueryClient({
			defaultOptions: { queries: { staleTime: Infinity, retry: false, refetchOnWindowFocus: false } },
		});

		seeds.forEach(({ queryKey, data }) => queryClient.setQueryData(queryKey, data));

		return queryClient;
	});

	return <QueryClientProvider client={client}>{children}</QueryClientProvider>;
};

/** Provides React Query, seeded from `parameters.seeds`. */
export const withQueryClient: Decorator = (Story, { id, parameters }) => (
	<SeededQueryClient key={id} seeds={(parameters.seeds as QuerySeed[] | undefined) ?? []}>
		<Story />
	</SeededQueryClient>
);
