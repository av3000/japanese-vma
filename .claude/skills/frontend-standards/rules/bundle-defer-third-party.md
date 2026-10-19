---
title: Defer Non-Critical Third-Party Libraries
impact: MEDIUM
impactDescription: keeps the initial chunk small
tags: bundle, third-party, analytics, monitoring, defer
---

## Defer Non-Critical Third-Party Libraries

**Impact: MEDIUM (keeps the initial chunk small)**

Analytics, logging and error tracking do not block the user. Load them with `import()` after the first render, and only when they are configured. `src/lib/monitoring/sentry.ts` is the pattern: `@sentry/react` loads only when `VITE_SENTRY_DSN` is set.

**Incorrect (Sentry is in the initial chunk for every visitor, even with no DSN):**

```ts
import * as Sentry from '@sentry/react';

export const initMonitoring = () => {
	Sentry.init({ dsn: import.meta.env.VITE_SENTRY_DSN });
};
```

**Correct (loaded on demand, and only when configured):**

```ts
let sentryModulePromise: Promise<typeof import('@sentry/react')> | null = null;

const loadSentry = async () => {
	sentryModulePromise ??= import('@sentry/react');
	return sentryModulePromise;
};

export const initMonitoring = async () => {
	if (!isSentryEnabled) {
		return;
	}
	const Sentry = await loadSentry();
	Sentry.init({ dsn: sentryDsn });
};
```

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
