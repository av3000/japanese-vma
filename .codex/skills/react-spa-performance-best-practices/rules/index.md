# Canonical Rule Index

This is the only index for the consolidated skill. Rule details live in the four SPA rule files below; Next.js-only guidance is isolated in `../references/nextjs-conditional.md`.

## Priority Order

1. **Startup and bundle:** remove app-wide boot blockers, split route bundles, defer non-critical third parties.
2. **Async and server state:** eliminate request waterfalls, use one cache owner, make query keys and invalidation explicit.
3. **Rerender and state:** reduce Context blast radius, fix component boundaries, memoize measured hotspots only.
4. **Rendering and browser:** avoid layout thrashing, duplicate listeners, sync storage hot paths, and wasteful list work.

## Rule Categories

| Category              | File                   | Use when                                                                                                     |
| --------------------- | ---------------------- | ------------------------------------------------------------------------------------------------------------ |
| Startup and bundle    | `bundle-startup.md`    | first paint, initial chunk size, Vite build output, route splitting, lazy imports, third-party scripts       |
| Async and data        | `async-data.md`        | duplicate fetches, stale data, query waterfalls, TanStack Query ownership, invalidation, Suspense boundaries |
| Rerender and state    | `rerender-state.md`    | broad rerenders, Context updates, unstable props, expensive derived values, transitions, deferred input      |
| Rendering and browser | `rendering-browser.md` | long lists, DOM reads/writes, event listeners, localStorage/cookies, script tags, JS hot paths               |

## Source Policy

- Keep Vercel's high-value React performance principles: waterfalls, bundle size, render work, event/storage costs, immutable data, and measured memoization.
- Prefer SPA adaptations where the original assumed Next.js: Vite chunks, React Router route lazy loading, TanStack Query, and browser-only startup.
- Exclude server-component and server-action rules from the default path. Use the Next.js reference only when the target project actually uses that runtime.
- Generalize organization-specific helpers, folder paths, and tokens. Do not copy project-only names into this skill.
