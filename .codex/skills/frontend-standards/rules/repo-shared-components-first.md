---
title: Reuse the Shared Components Before Writing Markup
impact: MEDIUM
impactDescription: one look and one behaviour per UI need
tags: repo, components, design-system
---

## Reuse the Shared Components Before Writing Markup

**Impact: MEDIUM (one look and one behaviour per UI need)**

`client/DESIGN.md` §7 maps each UI need to a shared component: `PageHeader`, `FilterBar`, `Button`, `Link`, `Alert`, `StatusPill`, `LevelBadge`, `JlptBar`, `Chip`, `Badge`, `FormControls`, `FormPage`, `DataTable`, `LibraryCards`, `Modal`, `Drawer`, `Icon`, `Spinner`, `PageLoading`. Use the listed component; extend it with a variant, with stories, rather than writing parallel markup.

**Incorrect (a hand-built inline error box next to an existing `Alert`):**

```tsx
<div className={styles.errorBox} role="alert">
	<strong>Error:</strong> {error.message}
</div>
```

**Correct:**

```tsx
<Alert tone="danger">{error.message}</Alert>
```

Source: `client/DESIGN.md` §7.
