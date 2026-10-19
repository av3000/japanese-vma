---
title: Test the Shared Helper Once, Plus One Representative Route
impact: LOW
impactDescription: coverage where the behaviour lives, without duplicate route tests
tags: repo, testing
---

## Test the Shared Helper Once, Plus One Representative Route

**Impact: LOW (coverage where the behaviour lives, without duplicate route tests)**

When several routes share a new write helper or adapter, put the thorough unit tests on the helper and add one representative route regression test. Do not copy near-identical integration tests into every route that uses it.

**Incorrect (the same add/remove scenarios repeated in four route test files):**

```text
routes/japanese/KanjiDetails/index.test.tsx     adds, removes, handles NaN id
routes/japanese/WordDetails/index.test.tsx      adds, removes, handles NaN id
routes/japanese/RadicalDetails/index.test.tsx   adds, removes, handles NaN id
routes/japanese/SentenceDetails/index.test.tsx  adds, removes, handles NaN id
```

**Correct:**

```text
api/catalogues/cataloguesForItem.test.ts        adds, removes, optimistic update, NaN id
routes/japanese/KanjiDetails/index.test.tsx     one bookmark round trip through the route
```

Source: `client/AGENTS.md` §11.
