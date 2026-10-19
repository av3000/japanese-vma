---
title: Let TanStack Query Own Server State
impact: MEDIUM
impactDescription: automatic deduplication, caching and cancellation
tags: client, tanstack-query, deduplication, data-fetching
---

## Let TanStack Query Own Server State

**Impact: MEDIUM (automatic deduplication, caching and cancellation)**

Fetch server data through a TanStack Query hook, not a `useEffect` plus `useState` pair. Every component that reads the same query key shares one request and one cache entry, gets the `signal` for cancellation, and is invalidated together after a mutation. In this repo the hook wraps a generated Orval client (see `repo-generated-client-first`).

**Incorrect (each instance fetches; no dedup, no cache, no cancellation, no invalidation):**

```tsx
const ArticleDetails = () => {
	const { article_id } = useParams<{ article_id: string }>();
	const [article, setArticle] = useState<ArticleDetailResource | null>(null);

	useEffect(() => {
		articleShow(article_id as string).then(setArticle);
	}, [article_id]);
};
```

**Correct (one request per key, shared by every reader):**

```tsx
export const useArticleQuery = (uuid: string | undefined) =>
	useQuery({
		queryKey: getArticleDetailQueryKey(uuid as string),
		queryFn: () => articleShow(uuid as string),
		enabled: !!uuid,
		select: mapArticleDetail,
	});
```

**Correct (mutations invalidate by key):**

```tsx
const queryClient = useQueryClient();

useMutation({
	mutationFn: (uuid: string) => articleDestroy(uuid),
	onSuccess: () => queryClient.invalidateQueries({ queryKey: articleKeys.lists() }),
});
```

Reference: [TanStack Query: important defaults](https://tanstack.com/query/v5/docs/framework/react/guides/important-defaults)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
