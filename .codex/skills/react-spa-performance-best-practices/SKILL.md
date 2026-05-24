---
name: react-spa-performance-best-practices
description: Use when writing, reviewing, or refactoring Vite React SPA code with slow first paint, large bundles, duplicate fetches, stale data, unnecessary rerenders, loading-state bugs, or React Router/TanStack Query performance concerns
---

# React SPA Performance Best Practices

## Overview

Optimize the measured bottleneck first. This skill defaults to browser-rendered Vite React SPAs using React Router and TanStack Query; Next.js, RSC, server actions, and SSR-only rules are conditional.

## When To Use

Use for React SPA work involving:

- slow first paint, startup blockers, or app-wide loading gates
- route bundle size, Vite chunking, dynamic imports, or third-party scripts
- duplicate requests, stale server state, query invalidation, or async waterfalls
- unnecessary rerenders, broad Context updates, expensive derived renders, or memoization questions
- browser rendering issues such as layout thrashing, event listeners, storage reads, or long lists

Do not use as a generic React style guide. For correctness bugs, debug the behavior first, then apply performance guidance.

## Workflow

1. Reproduce or measure before changing code: production build, network tab, React Profiler, bundle visualizer, query devtools, or console timings.
2. Identify the bottleneck category in `rules/index.md`.
3. Load only the relevant detailed rule file.
4. Prefer Vite, React Router, and TanStack Query patterns unless the project proves otherwise.
5. Verify the specific metric or behavior changed, and report any test or environment gap.

## Quick Routing

| Symptom | Read |
| --- | --- |
| Blank shell, auth boot delay, big initial JS | `rules/bundle-startup.md` |
| Duplicate fetches, stale data, request waterfalls | `rules/async-data.md` |
| Rerenders, Context churn, expensive input updates | `rules/rerender-state.md` |
| Long lists, layout thrash, event/storage overhead | `rules/rendering-browser.md` |
| Next.js, RSC, server actions, SSR hydration | `references/nextjs-conditional.md` |

## Common Mistakes

- Starting with `memo` before fixing app boot, bundle splits, data ownership, or Context shape.
- Blocking public SPA routes on auth/user/profile queries that only protected routes need.
- Porting `next/dynamic`, SWR, server actions, or `React.cache()` into a Vite SPA without a matching runtime.
- Keeping manual `useEffect` fetches next to TanStack Query for the same resource.
- Treating dev-mode duplicate effects as production duplicate requests without checking production behavior.
