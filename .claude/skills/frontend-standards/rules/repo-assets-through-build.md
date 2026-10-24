---
title: Ship Assets Through the Build, With Fallbacks for New CSS
impact: MEDIUM
impactDescription: the app works offline from CDNs and in older browsers
tags: repo, assets, fonts, css, production
---

## Ship Assets Through the Build, With Fallbacks for New CSS

**Impact: MEDIUM (the app works offline from CDNs and in older browsers)**

Treat the client as production. Fonts and other assets ship through the Vite build (`@fontsource` imports in `main.tsx`), not a CDN link. Newer CSS features get a fallback declaration first, and browser defaults stay where they already work.

**Incorrect:**

```html
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans" />
```

```css
.panel {
	background: color-mix(in oklab, var(--color-primary) 10%, white);
}
```

**Correct:**

```ts
// main.tsx
import '@fontsource/ibm-plex-sans/400.css';
```

```css
.panel {
	background: var(--color-primary-100);
	background: color-mix(in oklab, var(--color-primary) 10%, white);
}
```

Source: `client/DESIGN.md` §2 and §5.
