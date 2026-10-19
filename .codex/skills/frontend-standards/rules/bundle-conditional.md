---
title: Conditional Module Loading
impact: HIGH
impactDescription: loads large modules only when needed
tags: bundle, conditional-loading, lazy-loading
---

## Conditional Module Loading

**Impact: HIGH (loads large modules only when needed)**

Load a large module only when the feature that needs it switches on. `src/providers/contexts/socket-provider.tsx` does this for `laravel-echo` and `pusher-js`: they stay out of the main chunk until a signed-in user opens a socket.

**Incorrect (Echo and Pusher ship to every visitor, signed in or not):**

```tsx
import { createEcho } from '@/lib/echo/config/index';

useEffect(() => {
	if (!shouldStartWebSocket({ isConfigured, isAuthenticated, token })) {
		return;
	}
	setEchoClient(createEcho<'reverb'>(options));
}, [isConfigured, isAuthenticated, token]);
```

**Correct (dynamic import inside the branch that needs it):**

```tsx
useEffect(() => {
	if (!shouldStartWebSocket({ isConfigured, isAuthenticated, token })) {
		return;
	}

	let isActive = true;
	const connect = async () => {
		// Dynamic import keeps laravel-echo and pusher-js in their own chunk.
		const { createEcho } = await import('@/lib/echo/config/index');
		if (isActive) {
			setEchoClient(createEcho<'reverb'>(options));
		}
	};
	void connect();

	return () => {
		isActive = false;
	};
}, [isConfigured, isAuthenticated, token]);
```

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
