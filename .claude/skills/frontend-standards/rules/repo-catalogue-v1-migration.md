---
title: Migrate List Surfaces Onto Catalogue v1, Data Access First
impact: MEDIUM
impactDescription: migrations converge on one pattern instead of modernising legacy in place
tags: repo, migration, catalogues, legacy
---

## Migrate List Surfaces Onto Catalogue v1, Data Access First

**Impact: MEDIUM (migrations converge on one pattern instead of modernising legacy in place)**

For a list-style surface, first ask whether `/v1/catalogues` should back it. Migrate in this order: data access first; legacy endpoints behind a typed temporary adapter when v1 is not ready (`repo-adapter-todo-shape`); thin filter and param mapping to keep behaviour stable; then rebuild the route around the catalogue v1 query pattern. Do not modernise a legacy `/list` or `/lists` route tree in place. Preserve user-visible behaviour unless the change is explicit.

**Incorrect (the legacy route is converted to hooks but still drives `/lists` and `next_page_url`):**

```tsx
const SavedLists = () => {
	const [lists, setLists] = useState([]);
	const [url, setUrl] = useState('/lists');
	useEffect(() => {
		apiCall({ method: HttpMethod.GET, path: url }).then((res) => setLists((prev) => [...prev, ...res.lists.data]));
	}, [url]);
};
```

**Correct (the surface is rebuilt on the catalogue v1 list hook, as `DashboardCataloguesPanel` does):**

```tsx
const DashboardCataloguesPanel = () => {
	const { catalogues, isPending } = useInfiniteCatalogues({ filters: toCatalogueFilters(searchParams) });
};
```

Source: `client/AGENTS.md` §1 and §8.
