---
title: Store the Selected ID, Derive the Item
impact: MEDIUM
impactDescription: no stale copies of server data
tags: rerender, state, derived-state, selection
---

## Store the Selected ID, Derive the Item

**Impact: MEDIUM (no stale copies of server data)**

When the user selects something from a list, keep its ID in state and look the item up in the authoritative collection during render. A copied object goes stale as soon as the query refetches or a mutation updates the cache, and the view then shows two versions of the same item.

**Incorrect (the copied catalogue keeps its old `contains_item` after the toggle):**

```tsx
const [selectedList, setSelectedList] = useState<CatalogueForItem | null>(null);

<CatalogueBookmarkModal lists={lists} onSelect={(list) => setSelectedList(list)} />
{selectedList && <p>{selectedList.contains_item ? 'Saved' : 'Not saved'}</p>}
```

**Correct (the ID is the state; the item is derived from the current cache):**

```tsx
const [selectedListId, setSelectedListId] = useState<number | null>(null);
const selectedList = lists.find((list) => list.id === selectedListId) ?? null;

<CatalogueBookmarkModal lists={lists} onSelect={(list) => setSelectedListId(list.id)} />
{selectedList && <p>{selectedList.contains_item ? 'Saved' : 'Not saved'}</p>}
```

Selections worth sharing or keeping on refresh (tabs, filters, page) go in the URL with `useSearchParams` instead.

_Source: carried over from the retired repo skill `react-best-practices` (TanStack Query and SPA guidance)._
