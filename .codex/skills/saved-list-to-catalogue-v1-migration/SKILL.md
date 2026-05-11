---
name: saved-list-to-catalogue-v1-migration
description: Use when updating SavedList or custom-list routes, replacing legacy /list or /lists behavior, or deciding whether a list surface should move onto the v1 catalogue query and detail patterns
---

# SavedList To Catalogue V1 Migration

## Overview

For SavedList and custom-list work, the default direction is `/v1/catalogues`, not more investment in the legacy `/list` and `/lists` flows. Use the migrated Article surfaces as the quality bar and the dashboard catalogue panel as the list-query model.

## Use These References

- Positive migration precedent:
  - `src/routes/ArticlesList/index.tsx`
  - `src/routes/ArticleDetails/index.tsx`
  - `src/routes/ArticleCreate/index.tsx`
- Current catalogue-query direction:
  - `src/routes/Dashboard/DashboardCataloguesPanel.tsx`
  - `src/api/catalogues/catalogues.ts`
- Current debt to replace:
  - `src/routes/SavedLists/index.tsx`
  - `src/routes/SavedListDetails/index.tsx`
  - `src/routes/SavedListForm/index.tsx`
  - `src/routes/SavedListEdit/index.tsx`
  - `src/components/features/SavedList/SavedListItems.tsx`
  - `src/components/features/SavedList/SavedListItem/index.tsx`

## Decide The Target First

Move the surface to catalogue-v1 when any of these are true:

- the page is fundamentally a list or collection owned by a user
- pagination, search, sort, hashtags, or engagement stats belong to the route
- the legacy route is manually tracking `next_page_url`, `isLoading`, or POST-based search state
- the UI is branching on raw numeric SavedList types

If a needed backend capability is still missing, add a typed temporary adapter with a TODO naming the intended v1 replacement. Do not expand route-level `apiCall` usage instead.

## Migration Order

1. Replace route-owned fetching with a typed query module or feature hook.
2. Add a thin filter-to-query mapping layer so existing search UX stays stable.
3. Flatten paginated query results from React Query instead of storing `next_page_url` in component state.
4. Move list-type labels and branching into typed helpers or adapters.
5. Swap legacy modal markup for shared dialog primitives where the route is being touched.
6. Update comments to the current contract:
   - `objectUuid`
   - `parentObjectId`
   - `parentObjectType`
7. Only after data boundaries are clean, simplify the route and feature components.

## Traps To Remove During Migration

- `@ts-nocheck`
- class components and lifecycle methods
- `props.match` and `props.history`
- manual pagination/search state when React Query can own it
- copy-pasted list type arrays
- magic numbers in JSX
- old SavedList comment props like `objectId`, `objectType`, or `initialComments`

## Notes On Existing References

- `DashboardCataloguesPanel` is the best current example of the target query shape, even though its numeric `LIST_FILTER_TYPES` set is still temporary debt.
- `ArticleDetails/ArticleContent` shows the preferred modern comment usage, modal/controller composition, and generated catalogue-for-item path.
- PDF export remains the transitional adapter example there. Bookmark for-item reads are no longer the adapter precedent.

## Migration Checklist

- Is this surface better expressed as a catalogue list or catalogue detail?
- Did data access move before presentation cleanup?
- If a shared adapter boundary already exists, did the migration update all sibling routes consuming it or explicitly document why it is intentionally partial?
- Did search, sort, and pagination move into typed query state?
- Did numeric type logic leave the JSX?
- Did comments and modals move onto current shared contracts?
- Are any remaining legacy endpoints isolated behind a named adapter with a clear TODO?
