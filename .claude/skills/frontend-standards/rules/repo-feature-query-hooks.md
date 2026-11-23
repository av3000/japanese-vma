---
title: Server State Goes Through Feature-Scoped Query Hooks
impact: MEDIUM
impactDescription: one place owns keys, mapping and wire shape per feature
tags: repo, api, tanstack-query, hooks
---

## Server State Goes Through Feature-Scoped Query Hooks

**Impact: MEDIUM (one place owns keys, mapping and wire shape per feature)**

Wrap server reads in a hook under `src/api/<feature>/` that owns the query key, pagination, mapping to route-friendly data, and any awkward generated query-param shape. Routes and components consume the hook's result, never the wire shape. Precedents: `useInfiniteArticles` (flattened items plus total), `useArticleQuery` (mapped detail), `src/api/catalogues/details.ts`.

**Incorrect (the route spreads generated params, flattens pages and reads pagination fields itself):**

```tsx
const ArticlesList = () => {
	const query = useInfiniteQuery({
		queryKey: getArticleIndexQueryKey(filters),
		queryFn: ({ pageParam }) => articleIndex({ ...filters, page: pageParam }),
		initialPageParam: 1,
		getNextPageParam: (last) => (last.pagination.has_more ? last.pagination.page + 1 : undefined),
	});
	const articles = query.data?.pages.flatMap((page) => page.items) ?? [];
	const total = query.data?.pages[0]?.pagination.total ?? 0;
};
```

**Correct (`src/api/articles/hooks/useInfiniteArticles.ts`):**

```tsx
export const useInfiniteArticles = ({ enabled = true, filters = {} }: UseInfiniteArticlesOptions = {}) => {
	const query = useInfiniteQuery({
		queryKey: getInfiniteArticlesQueryKey(filters),
		queryFn: ({ pageParam, signal }) => articleIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextArticlesPageParam,
		enabled,
	});

	return { ...query, articles: query.data?.pages.flatMap((page) => page.items) ?? [], total: getArticlesTotal(query.data?.pages) };
};

// route
const { articles, total, isPending } = useInfiniteArticles({ filters });
```

Source: `client/AGENTS.md` §3.
