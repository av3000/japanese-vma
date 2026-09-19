# Async And Data Rules

## Core Rule

Make data ownership explicit before optimizing renders. In a Vite SPA, TanStack Query should usually own remote server state; React state should own local UI state.

## Apply These Rules

- Start independent async work in parallel with `Promise.all`; defer `await` until a branch actually needs the result.
- Avoid duplicate `useEffect` fetches for resources already represented by a query.
- Use stable query keys with every real input: user id, filters, pagination, locale, auth scope.
- Use `enabled` for dependent queries instead of running with missing ids and patching errors later.
- Set `staleTime` for data that should not refetch constantly.
- Use `select` when consumers need only a derived slice of query data.
- Invalidate targeted query keys after mutations; avoid broad `invalidateQueries()` unless the app really changed globally.
- Distinguish `isPending` from `isFetching`: first load can show a skeleton, background refetch should usually preserve existing UI.
- For manual fetches that remain, abort or ignore obsolete requests and keep effect dependencies honest.

## Example

```tsx
const userId = auth.user?.id

const cataloguesQuery = useQuery({
  queryKey: ["catalogues", userId, filters.page, filters.search],
  queryFn: () => getCatalogues({ userId: userId!, filters }),
  enabled: Boolean(userId),
  staleTime: 60_000,
  select: (response) => response.items,
})
```

## Suspense And Loading

Use Suspense or route fallbacks only around the part that truly waits for data. Do not hide the entire app shell because one dashboard panel is pending.

## Common Mistakes

- Fetching `/me` before mounting public routes.
- Copying query data into component state just to derive sorted or filtered views.
- Fixing duplicate dev requests with global flags before checking production behavior; React Strict Mode intentionally repeats some work in development.
- Using object query keys or filter objects that change meaning without changing the query key.
