# Seeding record

How `rules/` was built from upstream, so a later re-sync can repeat it. Seeded on 2026-10-19 (epic #502, STANDARDS-01).

## Upstream

- Repository: [vercel-labs/agent-skills](https://github.com/vercel-labs/agent-skills), MIT licence.
- Commit: `063bee94c3f4df8453406c830b0a7df0f2860278` (2026-08-28).
- Rules taken from `skills/react-best-practices/rules/` and `skills/composition-patterns/rules/`. Upstream files carry the footer `_Source: vercel-labs/agent-skills … at 063bee9._`; files changed for this stack say `adapted` or `rewritten`.

## Stack assumptions

Vite SPA with no server rendering, React 19.1 (`client/package-lock.json`), React Router v6, TanStack Query v5, Orval-generated clients, CSS Modules. No rule may recommend `next/*`, Server Components, server rendering, hydration or SWR.

## Dropped

| Upstream rule | Why |
| --- | --- |
| all ten `server-*` rules | Server-side rendering and React Server Components only |
| `async-api-routes` | Next.js API routes |
| `rendering-hydration-no-flicker`, `rendering-hydration-suppress-warning` | Hydration only exists with server rendering |
| `rendering-activity` | `<Activity>` needs React 19.2; the client is on 19.1 |
| `advanced-effect-event-deps`, `advanced-use-latest` | Both rely on `useEffectEvent`, which needs React 19.2. `advanced-event-handler-refs` covers the same need with a ref |

## Rewritten for this stack

| Rule | Change |
| --- | --- |
| `bundle-dynamic-imports` | `React.lazy` and `createLazyRoute` instead of `next/dynamic` |
| `client-swr-dedup` → `client-query-dedup` | TanStack Query hooks over generated clients instead of SWR |
| `async-suspense-boundaries` | Only the two cases that suspend here: lazy chunks and `useSuspenseQuery` |
| `bundle-conditional`, `bundle-preload`, `rendering-content-visibility`, `rendering-resource-hints` | Upstream had no Incorrect/Correct pair; pairs added from repo code, SSR remarks removed |
| `bundle-defer-third-party` | `import()` of `@sentry/react` (as `src/lib/monitoring/sentry.ts` does) instead of `next/dynamic` |
| `client-event-listeners` | Module-level shared listener instead of `useSWRSubscription` |

## Adapted (small edits)

- `bundle-analyzable-paths`: Next.js file-tracing paragraph and links removed.
- `bundle-barrel-imports`: Next.js `optimizePackageImports` section removed; Vite pre-bundling explained; example changed to an in-repo barrel; impact lowered from CRITICAL to MEDIUM, because Vite pre-bundles dependencies and Rollup tree-shakes ESM packages.
- `rendering-script-defer-async`: examples moved to `client/index.html`; `next/script` note removed.
- `rendering-hoist-jsx`, `rendering-animate-svg-wrapper`, `rendering-conditional-render`: Tailwind class names in examples replaced with CSS Module classes or shared components, so the examples do not contradict `repo-no-bootstrap-utility-classes`.
- `advanced-event-handler-refs`: `useEffectEvent` alternative removed (React 19.2).
- `client-external-store-sync`, `async-fetch-race-cleanup`, `rerender-lift-or-pass-down`: references to SSR, SWR and `server-*` rules removed.

## Local additions kept

Seven rules that are not upstream, written from the react.dev documentation ("You Might Not Need an Effect" and related pages): `async-fetch-race-cleanup`, `client-external-store-sync`, `rerender-context-value-memo`, `rerender-list-keys`, `rerender-lift-or-pass-down`, `rerender-no-effect-chains`, `rerender-reset-state-with-key`.

## Added

- From `composition-patterns`: `architecture-avoid-boolean-props`, `architecture-compound-components`, `patterns-explicit-variants`, `react19-no-forwardref`.
- From the retired repo skill `react-best-practices` (TanStack Query and SPA guidance with no upstream equivalent): `client-query-keys`, `client-query-options`, `client-query-pending-vs-fetching`, `async-public-routes-no-auth-wait`, `rerender-store-id-derive-item`.
- `repo-*` rules: each names its source section in `client/AGENTS.md` or `client/DESIGN.md`.

## Impact levels

Upstream's MEDIUM-HIGH is MEDIUM here and LOW-MEDIUM is LOW, so every rule maps to one review criticality.

## Re-syncing with upstream

1. Clone upstream at the new commit and diff `skills/react-best-practices/rules/` against the commit above.
2. Copy changed upstream rules over the matching files that still carry the plain upstream footer.
3. For files marked `adapted` or `rewritten`, merge by hand, re-applying the changes listed here.
4. Check new upstream rules against the stack assumptions before adding them, then add each to the `SKILL.md` index.
5. Update the commit in this file and in `SKILL.md`.
