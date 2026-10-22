---
title: Style With CSS Modules and Existing Tokens
impact: MEDIUM
impactDescription: one source of truth for colour, spacing and type
tags: repo, styling, css-modules, tokens
---

## Style With CSS Modules and Existing Tokens

**Impact: MEDIUM (one source of truth for colour, spacing and type)**

Component styles live in a `*.module.css` next to the component. Every colour, spacing, radius, font size, shadow, duration and z-index that has a token uses it (`src/styles/00-settings/`). If no token fits, add one to the right settings file with a comment saying why. Only reference tokens that exist: an undefined `var(--x)` fails silently.

**Incorrect:**

```css
.card {
	padding: 18px;
	color: #3a3a3a;
	border-radius: 6px;
	z-index: 1001;
	background: var(--color-surface-raised); /* no such token */
}
```

**Correct:**

```css
.card {
	padding: var(--spacing-md);
	color: var(--color-text-primary);
	border-radius: var(--border-radius-md);
	z-index: var(--z-index-dropdown);
	background: var(--color-neutral-0);
}
```

Source: `client/DESIGN.md` §2 and §4, `client/AGENTS.md` §5.
