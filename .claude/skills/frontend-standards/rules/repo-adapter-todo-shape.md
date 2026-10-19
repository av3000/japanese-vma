---
title: A Temporary Adapter Names Its Reason, Target and Removal
impact: MEDIUM
impactDescription: transitional debt stays visible and removable
tags: repo, api, legacy, adapter, todo
---

## A Temporary Adapter Names Its Reason, Target and Removal

**Impact: MEDIUM (transitional debt stays visible and removable)**

When a route must still call a legacy endpoint, isolate the call in one typed module under `src/api/<feature>/` and put a TODO at the top in this shape:

```ts
// TODO(#<issue> | no issue): temporary legacy adapter because <reason>. Target: <v1 endpoint or generated client>. Remove when <condition>.
```

Acceptable reasons: the endpoint is intentionally legacy or undocumented, the v1 endpoint does not exist yet, or the generated client is unusable. A documented v1 endpoint with a working generated client is never a reason (`repo-generated-client-first`).

**Incorrect (legacy call inline in the route, vague TODO):**

```tsx
// TODO: move to v1
const res = await apiCall({ method: HttpMethod.POST, path: '/lists/search', data: filters });
```

**Correct (one adapter module, typed, with the full TODO):**

```ts
// src/api/catalogues/legacySearch.ts
// TODO(no issue): temporary legacy adapter because /v1/catalogues has no full-text search yet. Target: catalogueIndex({ search }). Remove when the v1 index accepts `search`.
import axiosInstance from '@/services/axios';

export const searchCataloguesLegacy = async (filters: LegacyCatalogueSearch): Promise<LegacyCatalogueSearchResult> => {
	const response = await axiosInstance.post<LegacyCatalogueSearchResult>('/lists/search', filters);
	return response.data;
};
```

Source: `client/AGENTS.md` §3 and §8; template in `improve-frontend-codebase-architecture/templates/temporary-adapter.ts`.
