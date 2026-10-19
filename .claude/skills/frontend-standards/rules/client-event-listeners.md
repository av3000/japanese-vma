---
title: Deduplicate Global Event Listeners
impact: LOW
impactDescription: single listener for N components
tags: client, event-listeners, subscription
---

## Deduplicate Global Event Listeners

**Impact: LOW (single listener for N components)**

When many component instances need the same global event, register one listener at module level and fan out to the subscribed callbacks.

**Incorrect (N instances = N listeners):**

```tsx
function useKeyboardShortcut(key: string, callback: () => void) {
	useEffect(() => {
		const handler = (event: KeyboardEvent) => {
			if (event.metaKey && event.key === key) {
				callback();
			}
		};
		window.addEventListener('keydown', handler);
		return () => window.removeEventListener('keydown', handler);
	}, [key, callback]);
}
```

**Correct (N instances = 1 listener):**

```tsx
const keyCallbacks = new Map<string, Set<() => void>>();

const handleKeydown = (event: KeyboardEvent) => {
	if (event.metaKey) {
		keyCallbacks.get(event.key)?.forEach((callback) => callback());
	}
};

function useKeyboardShortcut(key: string, callback: () => void) {
	useEffect(() => {
		if (keyCallbacks.size === 0) {
			window.addEventListener('keydown', handleKeydown);
		}
		const callbacks = keyCallbacks.get(key) ?? new Set();
		callbacks.add(callback);
		keyCallbacks.set(key, callbacks);

		return () => {
			callbacks.delete(callback);
			if (callbacks.size === 0) {
				keyCallbacks.delete(key);
			}
			if (keyCallbacks.size === 0) {
				window.removeEventListener('keydown', handleKeydown);
			}
		};
	}, [key, callback]);
}
```

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
