---
name: client-frontend-standards
description: Use when changing React routes, hooks, forms, or modals in this client app and repository-specific guidance is needed on which local patterns to copy versus which legacy frontend patterns to avoid
---

# Client Frontend Standards

## Overview

Use the Article flows as the main positive precedent and treat SavedList routes as migration debt. This repo wants thin route shells, feature-scoped query hooks, shared dialog primitives, and typed adapters around generated or centralized API modules.

## Copy These First

- Route shape:
  - `src/routes/ArticlesList/index.tsx`
  - `src/routes/ArticleDetails/index.tsx`
  - `src/routes/ArticleCreate/index.tsx`
- Query and adapter boundaries:
  - `src/api/articles/hooks/useInfiniteArticles.ts`
  - `src/api/articles/details.ts`
  - `src/api/catalogues/catalogues.ts`
- Forms:
  - `src/components/features/articles/ArticleForm.tsx`
- Modal composition:
  - `src/routes/ArticleDetails/ArticleContent/index.tsx`
  - `src/components/features/DeleteInstanceModal`
  - `src/components/shared/DialogModal`
  - `src/components/shared/modals/ConfirmModal.tsx`

## Working Rules

- Keep route files thin: params, queries, mutations, loading/error states, then hand off to feature UI.
- Put server state in React Query hooks, not in route-owned pagination or loading state.
- Keep request code in Orval clients or typed service modules. If a legacy endpoint is unavoidable, hide it in a temporary adapter instead of calling `apiCall` throughout the route tree.
- Prefer `react-hook-form` plus Zod for new or migrated forms.
- Map backend enums and numeric categories once near the data boundary. Pass descriptive values into JSX.
- Reuse `useModal` or `useDialog` with shared dialog components on touched or migrated surfaces.

## Do Not Copy Forward

- `src/routes/SavedLists/index.tsx`
- `src/routes/SavedListDetails/index.tsx`
- `src/routes/SavedListForm/index.tsx`
- `src/routes/SavedListEdit/index.tsx`
- `src/components/features/SavedList/SavedListItems.tsx`
- `src/components/features/SavedList/SavedListItem/index.tsx`

Those files show the debt to remove:

- `@ts-nocheck`
- class components
- `componentWillMount`
- `props.match` and `props.history`
- raw string endpoints in route components
- copy-pasted type-label arrays
- numeric type logic inside JSX
- outdated comment props that do not match `CommentsBlock`

## Transitional Debt Note

`src/routes/ArticleDetails/ArticleContent/index.tsx` is still partly transitional. Copy its modal/controller composition, `CommentsBlock` usage, and query-driven detail flow. Do not copy its TODO-marked legacy `apiCall` escape hatches for bookmark and PDF actions without first checking whether a typed v1 adapter already exists.

## Quick Checklist

- Is the route shell thin?
- Is server data in a feature hook or centralized query?
- Is form state using `react-hook-form` plus Zod where applicable?
- Are modal interactions using shared dialog primitives?
- Are numeric backend categories mapped outside JSX?
- Did you avoid spreading new legacy `apiCall` calls or Redux patterns?
