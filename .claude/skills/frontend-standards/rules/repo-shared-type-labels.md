---
title: Labels Come From Shared Constants, Not Copied Arrays
impact: LOW
impactDescription: one label per type across the app
tags: repo, labels, constants
---

## Labels Come From Shared Constants, Not Copied Arrays

**Impact: LOW (one label per type across the app)**

Display labels for types and categories live in `src/shared/constants` (`CATALOGUE_TYPE_LABELS`, `CATALOGUE_TYPE_OPTIONS`). Do not copy a label array into a component.

**Incorrect:**

```tsx
const typeOptions = [
	{ value: 5, label: 'Radicals' },
	{ value: 6, label: 'Kanjis' },
	{ value: 7, label: 'Words' },
];
```

**Correct:**

```tsx
import { CATALOGUE_TYPE_OPTIONS } from '@/shared/constants/catalogues';

<Select options={CATALOGUE_TYPE_OPTIONS} {...register('type')} />
```

Source: `client/AGENTS.md` §6 and §9.
