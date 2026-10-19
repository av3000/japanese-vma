---
title: Use defer or async on Script Tags
impact: HIGH
impactDescription: eliminates render-blocking
tags: rendering, script, defer, async, performance
---

## Use defer or async on Script Tags

**Impact: HIGH (eliminates render-blocking)**

Script tags without `defer` or `async` block HTML parsing while the script downloads and executes. This delays First Contentful Paint and Time to Interactive.

- **`defer`**: Downloads in parallel, executes after HTML parsing completes, maintains execution order
- **`async`**: Downloads in parallel, executes immediately when ready, no guaranteed order

Use `defer` for scripts that depend on DOM or other scripts. Use `async` for independent scripts like analytics.

In this SPA the only hand-written script tags live in `client/index.html`. Everything imported from `src/` goes through the Vite bundle, which emits `type="module"` scripts that are deferred already.

**Incorrect (`client/index.html`: blocks parsing):**

```html
<head>
	<script src="https://example.com/analytics.js"></script>
	<script src="/scripts/utils.js"></script>
</head>
```

**Correct (non-blocking):**

```html
<head>
	<!-- Independent script: async -->
	<script src="https://example.com/analytics.js" async></script>
	<!-- DOM-dependent script: defer -->
	<script src="/scripts/utils.js" defer></script>
</head>
```

Reference: [MDN - Script element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/script#defer)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, adapted for this Vite SPA._
