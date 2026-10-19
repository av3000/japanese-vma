---
title: CSS content-visibility for Long Lists
impact: HIGH
impactDescription: faster initial render
tags: rendering, css, content-visibility, long-lists
---

## CSS content-visibility for Long Lists

**Impact: HIGH (faster initial render)**

Apply `content-visibility: auto` to the rows of a long list so the browser skips layout and paint for rows that are off screen. Pair it with `contain-intrinsic-size` so the scrollbar does not jump. In this repo the declaration goes in the list's CSS Module.

**Incorrect (all 1,000 rows are laid out and painted on first render):**

```css
/* WordList.module.css */
.row {
	padding: var(--spacing-sm) 0;
}
```

**Correct (off-screen rows are skipped until they scroll into view):**

```css
/* WordList.module.css */
.row {
	padding: var(--spacing-sm) 0;
	content-visibility: auto;
	contain-intrinsic-size: auto 3rem;
}
```

```tsx
<ul className={styles.list}>
	{words.map((word) => (
		<li key={word.uuid} className={styles.row} lang="ja">
			{word.word}
		</li>
	))}
</ul>
```

For 1,000 rows the browser skips layout and paint for the roughly 990 that are off screen.

_Source: vercel-labs/agent-skills `skills/react-best-practices` at `063bee9`, rewritten for this Vite SPA._
