---
title: Call Documented v1 Endpoints Through the Generated Client
impact: HIGH
impactDescription: one contract, no drift between hand-written calls and api.json
tags: repo, api, orval, generated-client
---

## Call Documented v1 Endpoints Through the Generated Client

**Impact: HIGH (one contract, no drift between hand-written calls and api.json)**

When a v1 endpoint is in `processor-api/api.json` and Orval generates a usable client for it (`src/api/generated/**`), call that client: directly, or through a feature hook that adds behaviour (query keys, mapping, invalidation). Do not add a parallel wrapper that only renames it, and do not route new writes through an `actions.ts`-style helper when a generated client exists. Add a custom adapter only when the endpoint is legacy, missing from the schema, or the generated client is actually unusable (`repo-adapter-todo-shape`).

**Incorrect (a hand-written wrapper duplicates the generated catalogue item clients):**

```ts
// src/api/catalogues/actions.ts
export const addItemToCatalogue = (catalogueUuid: string, itemId: number) =>
	axiosInstance.post(`/v1/catalogues/${catalogueUuid}/items`, { item_id: itemId });
```

**Correct (the generated clients, behind the module that owns catalogue for-item behaviour):**

```ts
// src/api/catalogues/cataloguesForItem.ts
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';

export const addOrRemoveCatalogueForItem = async ({ list, elementId, action }: AddOrRemoveArgs) => {
	if (action === 'add') {
		await catalogueAddItem(list.uuid, { item_id: elementId });
		return;
	}
	await catalogueRemoveItem(list.uuid, elementId);
};
```

Source: `client/AGENTS.md` §3, root `AGENTS.md` §2 (generated API contract workflow).
