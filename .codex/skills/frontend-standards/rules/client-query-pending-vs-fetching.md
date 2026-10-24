---
title: isPending for First Load, isFetching for Background Refetch
impact: MEDIUM
impactDescription: no skeleton flash on refetch
tags: client, tanstack-query, loading-states
---

## isPending for First Load, isFetching for Background Refetch

**Impact: MEDIUM (no skeleton flash on refetch)**

`isPending` is true only while there is no data yet: show the skeleton then. `isFetching` is also true during background refetches, polling and invalidation, while the old data is still on screen. Gating the page on `isFetching` replaces visible content with a skeleton every time the cache refreshes. Show background activity, if at all, as a small indicator.

**Incorrect (the list blanks to a skeleton every time it is invalidated or polled):**

```tsx
const { articles, isFetching } = useInfiniteArticles({ filters });

if (isFetching) {
	return <PageLoading family="list" />;
}
```

**Correct (skeleton only for the first load; the next page shows its own indicator):**

```tsx
const { articles, isPending, isFetchingNextPage } = useInfiniteArticles({ filters });

if (isPending) {
	return <PageLoading family="list" />;
}

return (
	<>
		<ArticleList articles={articles} />
		<LoadMore isFetchingNextPage={isFetchingNextPage} />
	</>
);
```

_Source: carried over from the retired repo skill `react-best-practices` (TanStack Query and SPA guidance)._
