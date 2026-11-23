---
title: Reuse the Generated Query-Key Helpers
impact: MEDIUM
impactDescription: invalidations hit the key the cache actually uses
tags: repo, tanstack-query, query-keys, orval
---

## Reuse the Generated Query-Key Helpers

**Impact: MEDIUM (invalidations hit the key the cache actually uses)**

When a hook wraps an Orval endpoint, build its key from the generated helper (`getArticleShowQueryKey`, `getCatalogueShowQueryKey`, `getArticleIndexQueryKey`), and spell each feature's keys once (`src/api/articles/keys.ts`). Pass the helper every param the request depends on (the filter object, the owner), so the key changes when they do (`client-query-keys`). A hand-written literal drifts from what the transport caches under, and its invalidations silently miss.

**Incorrect (the subscription patches a bare key the list never used; audit finding F-05):**

```ts
queryClient.setQueryData(['articles'], (old) => patchProcessingStatus(old, event));
```

**Correct:**

```ts
export const articleKeys = {
	lists: () => getArticleIndexQueryKey(),
	list: (params?: ArticleIndexParams) => getArticleIndexQueryKey(params),
	detail: (uuid: string, params?: ArticleShowParams) => getArticleShowQueryKey(uuid, params),
} as const;

queryClient.setQueriesData({ queryKey: articleKeys.lists() }, (old) => patchProcessingStatus(old, event));
```

Source: `client/AGENTS.md` §3.
