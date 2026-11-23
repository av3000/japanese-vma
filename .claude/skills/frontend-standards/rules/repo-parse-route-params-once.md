---
title: Parse Route Params Once, Near the Top
impact: MEDIUM
impactDescription: no NaN ids sent to v1 writes
tags: repo, routing, params, validation
---

## Parse Route Params Once, Near the Top

**Impact: MEDIUM (no NaN ids sent to v1 writes)**

`useParams()` values are strings or `undefined`. When one feeds a v1 write that expects a number, coerce it once with `Number(...)` at the top of the route, reuse that parsed value, and skip the write when it is not a number.

**Incorrect (coerced at every call site; a bad param posts `NaN`):**

```tsx
const { id } = useParams<{ id: string }>();

const onBookmark = (list: CatalogueForItem) =>
	addOrRemoveCatalogueForItem({ list, elementId: Number(id), action: 'add' });
const onUnbookmark = (list: CatalogueForItem) =>
	addOrRemoveCatalogueForItem({ list, elementId: parseInt(id!), action: 'remove' });
```

**Correct:**

```tsx
const { id } = useParams<{ id: string }>();
const entityId = Number(id);
const canWrite = Number.isFinite(entityId);

const onToggle = (list: CatalogueForItem, action: CatalogueForItemAction) => {
	if (!canWrite) {
		return;
	}
	void addOrRemoveCatalogueForItem({ list, elementId: entityId, action });
};
```

Source: `client/AGENTS.md` §2.
