---
title: No New Redux State
impact: HIGH
impactDescription: one owner per kind of state
tags: repo, state, redux
---

## No New Redux State

**Impact: HIGH (one owner per kind of state)**

**Enforced by ESLint** (`no-restricted-imports` for `react-redux`, `@reduxjs/toolkit` and `redux`). Reviewers skip this rule; the linter reports it.

Do not add Redux slices, selectors or `connect()` for new client state. Server state belongs to TanStack Query (`client-query-dedup`), local UI state to `useState` near the feature, and stable app-wide dependencies (auth, socket) to a Context provider. The remaining store in `src/store/` is legacy.

**Incorrect:**

```tsx
import { useSelector } from 'react-redux';

const ArticleFilters = () => {
	const filters = useSelector((state: RootState) => state.articles.filters);
};
```

**Correct (filters that should survive a refresh live in the URL):**

```tsx
const ArticlesList = () => {
	const [searchParams] = useSearchParams();
	const filters = toArticleListFilters(searchParams);
	const { articles, total, isPending } = useInfiniteArticles({ filters });
};
```

Source: `client/AGENTS.md` §7.
