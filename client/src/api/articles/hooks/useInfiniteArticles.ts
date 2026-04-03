import { useInfiniteQuery } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import { articleIndex } from '@/api/generated/article/article';
import type { ArticleIndexParams } from '@/api/generated/model/articleIndexParams';
import type { ArticleListResource } from '@/api/generated/model/articleListResource';

export type ArticleListFilters = Omit<ArticleIndexParams, 'page'>;

type UseInfiniteArticlesOptions = {
	enabled?: boolean;
	filters?: ArticleListFilters;
};

export const getInfiniteArticlesQueryKey = (filters: ArticleListFilters = {}) => ['articles', filters] as const;

const toPaginationNumber = (value: number | string | undefined) => {
	const parsedValue = typeof value === 'number' ? value : Number(value);

	return Number.isFinite(parsedValue) ? parsedValue : 0;
};

const toPaginationBoolean = (value: boolean | string | undefined) => {
	if (typeof value === 'boolean') {
		return value;
	}

	if (typeof value !== 'string') {
		return false;
	}

	const normalizedValue = value.trim().toLowerCase();

	if (normalizedValue === 'true') {
		return true;
	}

	if (normalizedValue === 'false' || normalizedValue.length === 0) {
		return false;
	}

	return toPaginationNumber(normalizedValue) > 0;
};

export const getNextArticlesPageParam = (lastPage: ArticleListResource) => {
	if (!toPaginationBoolean(lastPage.pagination.has_more)) {
		return undefined;
	}

	return toPaginationNumber(lastPage.pagination.page) + 1;
};

export const getArticlesTotal = (pages: ArticleListResource[] | undefined) => {
	return toPaginationNumber(pages?.[0]?.pagination.total);
};

export const useInfiniteArticles = ({ enabled = true, filters = {} }: UseInfiniteArticlesOptions = {}) => {
	const query = useInfiniteQuery<
		ArticleListResource,
		Error,
		InfiniteData<ArticleListResource>,
		ReturnType<typeof getInfiniteArticlesQueryKey>,
		number
	>({
		queryKey: getInfiniteArticlesQueryKey(filters),
		queryFn: ({ pageParam }) =>
			articleIndex({
				...filters,
				page: pageParam,
			}),
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
