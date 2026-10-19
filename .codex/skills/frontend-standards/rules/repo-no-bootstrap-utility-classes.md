---
title: No Bootstrap, Tailwind or Utility Classes
impact: HIGH
impactDescription: the retired frameworks stay retired
tags: repo, styling, bootstrap, tailwind
---

## No Bootstrap, Tailwind or Utility Classes

**Impact: HIGH (the retired frameworks stay retired)**

**Enforced by ESLint** (`no-restricted-imports`, for the packages) **and `npm run style:audit`** (for class names, held at zero by `style-budget.json`). Reviewers skip this rule; the tools report it.

Bootstrap, React-Bootstrap, Tailwind and other utility-class frameworks are removed. Do not import them and do not put their class names (`mt-3`, `d-flex`, `col-md-6`, `text-muted`) in `className`. Style with a CSS Module and the layout primitives (`repo-css-modules-tokens`, `repo-layout-primitives`).

**Incorrect (from the removed `SavedListForm` route):**

```tsx
<label htmlFor="title" className="mt-3">
	Title
</label>
```

**Correct:**

```tsx
<FormField label="Title" error={errors.title?.message}>
	{(control) => <Input {...control} {...register('title')} />}
</FormField>
```

Source: `client/AGENTS.md` §5, `client/DESIGN.md` §2.
