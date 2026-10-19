---
title: No Page-Owned Pagination, Search or Loading State for Server Data
impact: HIGH
impactDescription: no stale pages, races or duplicated loading flags
tags: repo, state, pagination, tanstack-query
---

## No Page-Owned Pagination, Search or Loading State for Server Data

**Impact: HIGH (no stale pages, races or duplicated loading flags)**

When the data comes from the server, React Query owns the pages, the loading flags and the cache. The page owns only the inputs: filters and page, ideally in the URL. Do not keep `next_page_url`, appended item arrays or `isLoading` in component state.

**Incorrect (from the removed `SavedLists` route):**

```tsx
this.state = { url: '/lists', lists: [], paginateObject: {}, searchTotal: '', isLoading: true };

fetchLists(givenUrl) {
	this.setState({ isLoading: true });
	return apiCall({ method: HttpMethod.GET, path: givenUrl }).then((res) => {
		newState.lists = [...newState.lists, ...res.lists.data];
		newState.url = res.lists.next_page_url;
	});
}
```

**Correct:**

```tsx
const { articles, total, isPending, hasNextPage, isFetchingNextPage, fetchNextPage } = useInfiniteArticles({ filters });
```

Source: `client/AGENTS.md` §3, §7 and §9.
