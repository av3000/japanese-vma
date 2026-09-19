---
name: react-best-practices
description: Use when writing, reviewing, or refactoring React components, hooks, client state, effects, forms, URL state, or Vite SPA performance
---

# React Best Practices

## Overview

Keep state minimal, render-derived values in render, and effects for external synchronization. For performance work, optimize the measured bottleneck first. Vite, React Router, and TanStack Query are defaults only when the project uses them; server-rendered rules are conditional.

## When To Use

Use for React work involving:

- component boundaries, prop APIs, reusable layout, TypeScript props, forms, or hardcoded values
- `useState`, `useEffect`, derived state, stale closures, cleanup, manual fetching, or custom hooks
- selected items, filters, pagination, or UI state that may belong in the URL
- slow first paint, startup blockers, or app-wide loading gates
- route bundle size, Vite chunking, dynamic imports, or third-party scripts
- duplicate requests, stale server state, query invalidation, or async waterfalls
- unnecessary rerenders, broad Context updates, expensive derived renders, or memoization questions
- browser rendering issues such as layout thrashing, event listeners, storage reads, or long lists

For correctness bugs, debug the behavior first, then apply these rules. Do not force Vite or TanStack Query conventions into a different runtime.

## Workflow

1. Find the authoritative source for each value: props, local UI state, server cache, URL, or derived render value.
2. Read `rules/components-state-effects.md` for component, state, effect, and data-flow work.
3. For performance work, measure first and use `rules/index.md` to select the relevant rule file.
4. Use the project's data and routing runtime; prefer its established cache owner over a parallel fetch path.
5. Verify the specific behavior or metric changed, and report any test or environment gap.

## Quick Routing

| Symptom | Read |
| --- | --- |
| Props, components, forms, state, effects, URL state, manual fetching | `rules/components-state-effects.md` |
| Blank shell, auth boot delay, big initial JS | `rules/bundle-startup.md` |
| Duplicate fetches, stale data, request waterfalls | `rules/async-data.md` |
| Rerenders, Context churn, expensive input updates | `rules/rerender-state.md` |
| Long lists, layout thrash, event/storage overhead | `rules/rendering-browser.md` |
| Next.js, RSC, server actions, SSR hydration | `references/nextjs-conditional.md` |

## Common Mistakes

- Storing a value that props, server data, or another state value can derive.
- Using an effect for calculations, or combining unrelated external synchronizations in one effect.
- Passing raw setters to reusable children instead of an event that names the user's intent.
- Starting with `memo` before fixing app boot, bundle splits, data ownership, or Context shape.
- Blocking public SPA routes on auth/user/profile queries that only protected routes need.
- Porting `next/dynamic`, SWR, server actions, or `React.cache()` into a Vite SPA without a matching runtime.
- Keeping manual `useEffect` fetches next to TanStack Query for the same resource.
- Treating dev-mode duplicate effects as production duplicate requests without checking production behavior.
