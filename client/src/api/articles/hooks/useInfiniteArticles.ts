import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { articleIndex, getArticleIndexQueryKey } from '@/api/generated/article/article';
import type { ArticleIndexQueryError } from '@/api/generated/article/article';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';

export type ArticleListFilters = Omit<ArticleIndexParams, 'page'>;

type UseInfiniteArticlesOptions = {
	enabled?: boolean;
	filters?: ArticleListFilters;
};

export const getInfiniteArticlesQueryKey = (filters: ArticleListFilters = {}) => getArticleIndexQueryKey(filters);

export const getNextArticlesPageParam = (lastPage: ArticleListResource) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getArticlesTotal = (pages: ArticleListResource[] | undefined) => pages?.[0]?.pagination.total ?? 0;

export const useInfiniteArticles = ({ enabled = true, filters = {} }: UseInfiniteArticlesOptions = {}) => {
	const query = useInfiniteQuery<
		ArticleListResource,
		ArticleIndexQueryError,
		InfiniteData<ArticleListResource>,
		ReturnType<typeof getInfiniteArticlesQueryKey>,
		number
	>({
		queryKey: getInfiniteArticlesQueryKey(filters),
		queryFn: ({ pageParam, signal }) => articleIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextArticlesPageParam,
		enabled,
	});

	const articles = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = getArticlesTotal(query.data?.pages);

	return {
		...query,
		articles,
		total,
	};
};
