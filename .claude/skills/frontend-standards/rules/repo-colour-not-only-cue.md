---
title: Colour Is Never the Only Cue
impact: HIGH
impactDescription: status and level stay readable without colour vision
tags: repo, accessibility, colour
---

## Colour Is Never the Only Cue

**Impact: HIGH (status and level stay readable without colour vision)**

Status, level and validity carry visible text or an icon as well as a colour. `StatusPill` requires a `label`; `LevelBadge` prints the level.

**Incorrect (a coloured dot is the whole message):**

```tsx
<span className={styles.dot} data-tone={article.status === 'approved' ? 'success' : 'warning'} />
```

**Correct:**

```tsx
<StatusPill {...articleStatusPill(article.status)} />
// renders an icon and the visible label, for example "Pending review"
```

Source: `client/DESIGN.md` §2.
