---
title: Catalogue For-Item Reads and Writes Live in cataloguesForItem.ts
impact: MEDIUM
impactDescription: bookmark state stays consistent across the four dictionary detail routes
tags: repo, catalogues, bookmarks, api
---

## Catalogue For-Item Reads and Writes Live in cataloguesForItem.ts

**Impact: MEDIUM (bookmark state stays consistent across the four dictionary detail routes)**

"Which catalogues contain this item, and add or remove it" belongs behind `src/api/catalogues/cataloguesForItem.ts`. Reads use the generated v1 `catalogueForItem`; writes use the generated `catalogueAddItem` and `catalogueRemoveItem`, from that module or straight from the route. Read membership from `contains_item`, not the legacy `elementBelongsToList`. Do not add new writes to `src/api/catalogues/actions.ts`. `KanjiDetails`, `WordDetails`, `RadicalDetails` and `SentenceDetails` share this pattern: change them together.

**Incorrect (legacy membership check and a write through the old actions module):**

```tsx
const isBookmarked = elementBelongsToList(lists, kanji.id);
await addToList({ listId: list.id, elementId: kanji.id });
```

**Correct:**

```tsx
const lists = await fetchCataloguesForItem(kanji.id, { types: [ObjectTemplateType.KANJI] });
const { isBookmarked } = deriveCatalogueWidgetState(lists, instanceObjectType);
await addOrRemoveCatalogueForItem({ list, elementId: kanji.id, action: 'add' });
```

Source: `client/AGENTS.md` §3 and §8.
