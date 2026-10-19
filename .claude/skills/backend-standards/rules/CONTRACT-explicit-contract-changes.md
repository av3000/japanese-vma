---
title: Contract Changes Are Explicit; Legacy Behaviour Is Kept on Purpose
impact: MEDIUM
impactDescription: no silent payload, response or auth drift for clients
tags: contract, migration, legacy
---

## Contract Changes Are Explicit; Legacy Behaviour Is Kept on Purpose

**Impact: MEDIUM (no silent payload, response or auth drift for clients)**

A change to a payload, response, status code or auth requirement is stated in the issue and the PR, and travels with the regenerated `api.json`. A migration preserves the legacy behaviour it replaces unless the issue says otherwise.

**Incorrect (`f3ab0554^:client/src/api/comments.ts` era: each parent had its own comment endpoint, replaced by `POST /api/v1/comments`; doing that without stating it would break every caller silently):**

```ts
const response = await axios.post(`${parentObjectType}/${parentObjectId}/comment`, requestPayload);
```

**Correct (`routes/api.php`: the RET-ART-01 retirement names every replacement, and the PR and issue stated it):**

```php
// The whole legacy Article family was retired here (RET-ART-01) once every route had a v1
// replacement and zero routed React callers:
//
//   articles, article/{id}                -> GET  v1/articles, GET v1/articles/{id}
//   articles/search                       -> GET  v1/articles query filters (AFM-01..AFM-07)
```

Source: `.ai/guidelines/backend-response-and-errors.md`, `.ai/guidelines/repository.md`, `processor-api/AGENTS.md` §2 and §7.
