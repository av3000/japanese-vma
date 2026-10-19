---
title: Keep the Global Focus Outline
impact: HIGH
impactDescription: keyboard users can see where they are
tags: repo, accessibility, focus
---

## Keep the Global Focus Outline

**Impact: HIGH (keyboard users can see where they are)**

The focus outline comes from `src/styles/02-generic/outline.css`. A component may change its colour, never remove it.

**Incorrect:**

```css
.cardLink:focus {
	outline: none;
}
```

**Correct:**

```css
.cardLink:focus-visible {
	outline-color: var(--color-primary);
}
```

Source: `client/DESIGN.md` §2.
