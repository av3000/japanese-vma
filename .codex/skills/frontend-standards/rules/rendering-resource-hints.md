---
title: Use React DOM Resource Hints
impact: HIGH
impactDescription: reduces load time for critical resources
tags: rendering, preload, preconnect, prefetch, resource-hints
---

## Use React DOM Resource Hints

**Impact: HIGH (reduces load time for critical resources)**

React DOM can tell the browser about resources it will need soon. In an SPA, call these from a component or an event handler before the resource is requested.

- **`prefetchDNS(href)`**: resolve DNS for a domain you will connect to later
- **`preconnect(href)`**: open the connection (DNS, TCP, TLS) to a server you will fetch from soon
- **`preload(href, options)`**: fetch a stylesheet, font, script or image the current view needs
- **`preloadModule(href)`**: fetch an ES module for a likely next step
- **`preinit(href, options)`**: fetch and evaluate a stylesheet or script

**Incorrect (the API connection starts only when the first query fires):**

```tsx
const App = () => (
	<QueryClientProvider client={queryClient}>
		<Router>
			<AppContent />
		</Router>
	</QueryClientProvider>
);
```

**Correct (connection setup overlaps with the bundle evaluating):**

```tsx
import { preconnect } from 'react-dom';

const App = () => {
	preconnect(import.meta.env.VITE_API_URL);

	return (
		<QueryClientProvider client={queryClient}>
			<Router>
				<AppContent />
			</Router>
		</QueryClientProvider>
	);
};
```

Use hints sparingly. One `preconnect` to the API host is worth it; hints for every third party compete with what the current route needs. Fonts here ship through the build (`@fontsource`), so they need no hint.

Reference: [React DOM resource preloading APIs](https://react.dev/reference/react-dom#resource-preloading-apis)

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
