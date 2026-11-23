---
title: Respect prefers-reduced-motion
impact: MEDIUM
impactDescription: no motion for people who asked the OS to avoid it
tags: repo, accessibility, motion
---

## Respect prefers-reduced-motion

**Impact: MEDIUM (no motion for people who asked the OS to avoid it)**

Any animation or transition that moves things is switched off, or replaced with an opacity change, inside `@media (prefers-reduced-motion: reduce)`. Durations and easing come from `--duration-*` and `--ease-*`.

**Incorrect:**

```css
.drawer {
	transition: transform 300ms ease-out;
}
```

**Correct:**

```css
.drawer {
	transition: transform var(--duration-md) var(--ease-base);
}

@media (prefers-reduced-motion: reduce) {
	.drawer {
		transition: opacity var(--duration-sm) linear;
	}
}
```

Source: `client/DESIGN.md` §2 and §6.
