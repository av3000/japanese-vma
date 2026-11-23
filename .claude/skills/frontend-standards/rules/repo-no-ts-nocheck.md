---
title: No @ts-nocheck
impact: HIGH
impactDescription: type errors stay visible
tags: repo, typescript
---

## No @ts-nocheck

**Impact: HIGH (type errors stay visible)**

**Enforced by ESLint** (`@typescript-eslint/ban-ts-comment`, `ts-nocheck` only, in `client/eslint.config.mjs`). Reviewers skip this rule; the linter reports it.

`// @ts-nocheck` switches off type checking for the whole file, so a wrong prop or a changed generated type ships unnoticed. Fix the types instead. If one line genuinely cannot be typed, use `// @ts-expect-error` with a reason on that line only.

**Incorrect (top of the removed `SavedLists` route):**

```tsx
// @ts-nocheck
/* eslint-disable */
import React, { Component } from 'react';
```

**Correct:**

```tsx
import type { CatalogueListResource } from '@/api/generated/model/catalogueListResource';

const CatalogueCard = ({ catalogue }: { catalogue: CatalogueListResource }) => <LibraryCard title={catalogue.title} />;
```

Source: `client/AGENTS.md` §7.
