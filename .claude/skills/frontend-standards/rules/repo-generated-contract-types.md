---
title: API Contract Types Come From the Generated Models
impact: MEDIUM
impactDescription: the client type changes when the backend contract does
tags: repo, types, orval, enums
---

## API Contract Types Come From the Generated Models

**Impact: MEDIUM (the client type changes when the backend contract does)**

Request and response types, and any enum the backend schema defines, come from `src/api/generated/model/**`. `src/shared/constants/enums` holds UI labels, legacy numeric IDs and app-local helpers; it is not a substitute for a generated contract type.

**Incorrect (a hand-written copy of the resource shape):**

```ts
interface Catalogue {
	uuid: string;
	title: string;
	type: number;
	items_count: number;
}

export const fetchCatalogue = (uuid: string): Promise<Catalogue> => catalogueShow(uuid);
```

**Correct:**

```ts
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';

export interface MappedCatalogue extends CatalogueDetailResource {
	displayName: string;
	formattedDate: string;
}
```

Source: `client/AGENTS.md` §3.
