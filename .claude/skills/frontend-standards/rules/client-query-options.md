---
title: Use enabled, staleTime and select Deliberately
impact: MEDIUM
impactDescription: avoids failed requests, refetch churn and re-render noise
tags: client, tanstack-query, enabled, staletime, select
---

## Use enabled, staleTime and select Deliberately

**Impact: MEDIUM (avoids failed requests, refetch churn and re-render noise)**

- **`enabled`**: a dependent query waits for its input. Do not run it with `undefined` and patch the error afterwards.
- **`staleTime`**: data that cannot change during a session (a study deck pinned by its seed, a dictionary entry) should not refetch on every mount and focus.
- **`select`**: map the wire shape to what the view needs inside the query, so components re-render only when the mapped value changes and no component copies query data into state to reshape it.

**Incorrect (runs with no uuid, refetches a frozen deck, reshapes in the component):**

```tsx
const { data } = useFlashcardDeckShow(catalogueUuid ?? '', params);
const [deck, setDeck] = useState<StudyDeck | null>(null);

useEffect(() => {
	if (data) setDeck(mapStudyDeck(data));
}, [data]);
```

**Correct:**

```tsx
export const useStudyDeck = (catalogueUuid: string | undefined, config: StudyConfig, enabled = true) =>
	useFlashcardDeckShow<StudyDeck>(catalogueUuid ?? '', studyConfigToParams(config), {
		query: {
			enabled: Boolean(catalogueUuid) && enabled,
			// A deck is a pure function of catalogue, config and seed.
			staleTime: Infinity,
			select: mapStudyDeck,
		},
	});
```

_Source: carried over from the retired repo skill `react-best-practices` (TanStack Query and SPA guidance)._
