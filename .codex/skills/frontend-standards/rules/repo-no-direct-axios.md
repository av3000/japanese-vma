---
title: No Direct axios Calls or Raw Endpoint Strings
impact: HIGH
impactDescription: keeps every request on the typed, interceptor-backed transport
tags: repo, api, axios, transport
---

## No Direct axios Calls or Raw Endpoint Strings

**Impact: HIGH (keeps every request on the typed, interceptor-backed transport)**

**Enforced by ESLint** (`no-restricted-imports` for `@/services/axios` and the default `axios` import, `no-restricted-globals` for `fetch`). Reviewers skip this rule; the linter reports it.

`@/services/axios` is the transport the generated clients use through `src/services/orval-mutator.ts`. Routes, components and feature hooks never import it, never import `axios` itself, and never spell endpoint paths. The only exception is a named temporary adapter for a legacy endpoint (`repo-adapter-todo-shape`).

**Incorrect (raw endpoint strings on the shared instance, inside a route; from the removed `SavedListDetails` route):**

```tsx
const res = await apiCall({ method: HttpMethod.GET, path: `/list/${list_id}` });
const likeRes = await apiCall({ method: HttpMethod.POST, path: `/list/${list_id}/checklike` });
await apiCall({ method: HttpMethod.DELETE, path: `/api/list/${list_id}` });
```

**Correct (generated clients behind feature hooks):**

```tsx
const { data: catalogue, isPending, isError } = useCatalogueQuery(uuid);
const likeMutation = useLikeCatalogueMutation(uuid);
const deleteMutation = useDeleteCatalogueMutation();
```

Source: `client/AGENTS.md` §3 and §7.
