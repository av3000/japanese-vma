---
title: Query Keys Name Every Input
impact: HIGH
impactDescription: prevents stale or cross-user data
tags: client, tanstack-query, query-keys, invalidation
---

## Query Keys Name Every Input

**Impact: HIGH (prevents stale or cross-user data)**

A query key must contain every value the `queryFn` reads: filters, page, owner, locale, include flags. If a value changes and the key does not, React Query serves the old cache entry for the new input. Spell keys in one place per feature (`src/api/articles/keys.ts`) and invalidate the narrowest prefix that changed.

**Incorrect (the filters change the request but not the key):**

```tsx
useInfiniteQuery({
	queryKey: ['articles'],
	queryFn: ({ pageParam }) => articleIndex({ ...filters, page: pageParam }),
	initialPageParam: 1,
	getNextPageParam: getNextArticlesPageParam,
});

// after creating one article, every query in the app refetches
queryClient.invalidateQueries();
```

**Correct (the key carries the filters; invalidation targets the list prefix):**

```tsx
export const articleKeys = {
	lists: () => getArticleIndexQueryKey(),
	list: (params?: ArticleIndexParams) => getArticleIndexQueryKey(params),
	detail: (uuid: string, params?: ArticleShowParams) => getArticleShowQueryKey(uuid, params),
} as const;

useInfiniteQuery({
	queryKey: articleKeys.list(filters),
	queryFn: ({ pageParam, signal }) => articleIndex({ ...filters, page: pageParam }, undefined, signal),
	initialPageParam: 1,
	getNextPageParam: getNextArticlesPageParam,
});

queryClient.invalidateQueries({ queryKey: articleKeys.lists() });
```

_Source: carried over from the retired repo skill `react-best-practices` (TanStack Query and SPA guidance)._
