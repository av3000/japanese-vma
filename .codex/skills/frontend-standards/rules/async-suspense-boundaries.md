---
title: Suspense Only Around What Actually Suspends
impact: HIGH
impactDescription: faster first paint, no app-wide spinner
tags: async, suspense, lazy, useSuspenseQuery
---

## Suspense Only Around What Actually Suspends

**Impact: HIGH (faster first paint, no app-wide spinner)**

In this SPA two things suspend: a `React.lazy` chunk that has not loaded yet, and a TanStack `useSuspenseQuery` whose data is not cached yet. Wrap the smallest subtree that waits, so the header, navigation and the rest of the layout render at once. Never put one `Suspense` around the whole router or app shell.

Plain `useQuery` does not suspend. A route that uses it gates on `isPending` itself (see `client-query-pending-vs-fetching`) and needs no boundary.

**Incorrect (one boundary around the router: every lazy chunk blanks the whole app):**

```tsx
<Suspense fallback={<Spinner />}>
	<Routes>
		<Route path="/articles" element={<ArticlesListPage />} />
		<Route path="/catalogues/:uuid" element={<CatalogueDetailsPage />} />
	</Routes>
</Suspense>
```

**Correct (each lazy route owns its boundary, and below-the-fold blocks own theirs):**

```tsx
// createLazyRoute wraps only the route body in RouteLoadingBoundary.
const CatalogueDetailsPage = createLazyRoute(() => import('@/routes/CatalogueDetails'), { family: 'detail' });

// Inside CatalogueContent the comments chunk suspends; the catalogue body does not.
<Suspense fallback={null}>
	<LazyCommentsBlock parent="catalogue" entityId={catalogue.id} entityUuid={catalogue.uuid} />
</Suspense>
```

**Correct (`useSuspenseQuery`: the boundary sits right above the panel that reads it):**

```tsx
const RelatedArticlesPanel = ({ uuid }: { uuid: string }) => {
	const { data } = useSuspenseQuery({ queryKey: articleKeys.detail(uuid), queryFn: () => articleShow(uuid) });
	return <ArticleList articles={data.related} />;
};

<Suspense fallback={<Spinner />}>
	<RelatedArticlesPanel uuid={article.uuid} />
</Suspense>
```

**When not to add a nested boundary:** data the layout needs to position itself, or a query fast enough that a fallback would only flash. Gate those on `isPending` in the route instead.

Reference: [Suspense](https://react.dev/reference/react/Suspense), [TanStack Query suspense](https://tanstack.com/query/v5/docs/framework/react/guides/suspense)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
