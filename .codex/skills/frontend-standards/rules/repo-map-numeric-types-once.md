---
title: Map Numeric Types Once, Near the Data Boundary
impact: MEDIUM
impactDescription: no magic numbers spread through rendering code
tags: repo, types, enums, mapping
---

## Map Numeric Types Once, Near the Data Boundary

**Impact: MEDIUM (no magic numbers spread through rendering code)**

When a backend type or list category arrives as a number, map it once in a helper or adapter (`src/shared/constants/catalogues.ts`) and pass descriptive values into components. No `type === 2 || type === 6` in JSX.

**Incorrect (from the removed `SavedListItem` component):**

```tsx
const isJlptList = type === 2 || type === 6;
```

**Correct (`src/shared/constants/catalogues.ts`):**

```ts
export const studyFamilyFor = (catalogueType: number): StudyFamily | null => {
	/* looks the number up in CATALOGUE_FAMILIES once */
};

// component
const family = studyFamilyFor(catalogue.type);
{family === 'kanji' && <JlptBar counts={catalogue.jlpt} />}
```

Source: `client/AGENTS.md` §6 and §9.
