---
title: The Accent Colour Means JLPT Level
impact: LOW
impactDescription: the accent keeps one meaning
tags: repo, colour, design-system
---

## The Accent Colour Means JLPT Level

**Impact: LOW (the accent keeps one meaning)**

The vermilion accent (`--color-accent*`, `--color-badge-accent-*`) marks JLPT level only: `LevelBadge`, `JlptBar`, glyph covers. Buttons, errors and decoration use the primary, status or neutral tokens.

**Incorrect:**

```css
.deleteButton {
	background: var(--color-accent);
}
```

**Correct:**

```css
.deleteButton {
	background: var(--color-error);
}
```

Source: `client/DESIGN.md` §3.
