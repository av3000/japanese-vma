---
title: Preload Based on User Intent
impact: MEDIUM
impactDescription: reduces perceived latency
tags: bundle, preload, user-intent, hover
---

## Preload Based on User Intent

**Impact: MEDIUM (reduces perceived latency)**

A lazy chunk starts downloading only when it first renders. When the next step is predictable, such as hovering or focusing a link to a lazy route, start the same `import()` early. The module promise is cached, so the later `lazy()` call reuses it.

**Incorrect (the create-article chunk downloads only after the click, so the page shows a loader):**

```tsx
<Button to="/articles/create" variant="primary">
	New article
</Button>
```

**Correct (the download starts on hover or focus; the click usually finds it cached):**

```tsx
const preloadArticleCreate = () => void import('@/routes/ArticleCreate');

<Button to="/articles/create" variant="primary" onMouseEnter={preloadArticleCreate} onFocus={preloadArticleCreate}>
	New article
</Button>
```

Use the same import specifier as `routes.tsx`, so both resolve to one chunk.

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
