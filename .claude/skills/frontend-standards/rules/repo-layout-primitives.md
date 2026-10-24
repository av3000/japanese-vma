---
title: Lay Out With Container, Stack, Cluster and Grid
impact: MEDIUM
impactDescription: consistent spacing and breakpoints without per-page CSS
tags: repo, styling, layout
---

## Lay Out With Container, Stack, Cluster and Grid

**Impact: MEDIUM (consistent spacing and breakpoints without per-page CSS)**

Page and section layout uses the primitives in `src/components/shared/layout`. Their `gap` props take spacing token names (`'md'`, `'lg'`). Write layout CSS only for what the primitives cannot express.

**Incorrect (a CSS Module re-implements a vertical stack):**

```css
.skeleton {
	display: flex;
	flex-direction: column;
	gap: 16px;
}
```

**Correct (`ArticlesListSkeleton.tsx`):**

```tsx
<Stack gap="md" data-testid="articles-list-skeleton" aria-hidden="true">
	{rows}
</Stack>
```

Source: `client/DESIGN.md` §2, `client/AGENTS.md` §5.
