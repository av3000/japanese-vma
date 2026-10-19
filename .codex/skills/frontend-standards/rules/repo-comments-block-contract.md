---
title: Wire Comments Through CommentsBlock's Current Props
impact: MEDIUM
impactDescription: reads and writes hit the right parent and cache
tags: repo, comments, contract
---

## Wire Comments Through CommentsBlock's Current Props

**Impact: MEDIUM (reads and writes hit the right parent and cache)**

`CommentsBlock` takes `parent` (which kind of thing is commented on), `entityId` and `entityUuid`. `parent` selects the read transport, the cache key and the entity template in one go, so callers never pass a template uuid or a route segment. Precedents: `ArticleContent`, `CatalogueContent`, `PostContent`.

**Incorrect (legacy props that no longer match the contract):**

```tsx
<CommentsBlock objectId={list.id} objectType="list" path={`/list/${list.id}`} />
```

**Correct (`CatalogueContent.tsx`):**

```tsx
<LazyCommentsBlock parent="catalogue" entityId={catalogue.id} entityUuid={catalogue.uuid} />
```

Source: `client/AGENTS.md` §7 and §8.
