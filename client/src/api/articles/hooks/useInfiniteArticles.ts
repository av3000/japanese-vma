import { useInfiniteQuery } from '@tanstack/react-query';
import { articleIndex } from '@/api/generated/article';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';

export type ArticleListFilters = Omit<ArticleIndexParams, 'page'>;

type UseInfiniteArticlesOptions = {
	enabled?: boolean;
	filters?: ArticleListFilters;
};

export const getInfiniteArticlesQueryKey = (filters: ArticleListFilters = {}) => ['articles', filters] as const;

export const useInfiniteArticles = ({ enabled = true, filters = {} }: UseInfiniteArticlesOptions = {}) => {
	const query = useInfiniteQuery({
		queryKey: getInfiniteArticlesQueryKey(filters),
		queryFn: ({ pageParam }) =>
			articleIndex({
				...filters,
				page: Number(pageParam),
			}),
		initialPageParam: 1,
		getNextPageParam: (lastPage) => {
			return lastPage.data.pagination.has_more ? lastPage.data.pagination.page + 1 : undefined;
		},
		enabled,
	});

	const articles = query.data?.pages.flatMap((page) => page.data.items) ?? [];
	const total = query.data?.pages[0]?.data.pagination.total ?? 0;

	return {
		...query,
		articles,
		total,
	};
};
