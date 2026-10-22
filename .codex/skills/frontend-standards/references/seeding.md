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
| `advanced-effect-event-deps`, `advanced-use-latest` | Both rely on `useEffectEvent`, which needs React 19.2 |

## Dropped after review (#502)

Generic upstream rules that a reviewer would not cite in this app: micro-optimisations on lists capped at 100 rows, patterns that only apply to server handlers, and APIs or assets the client does not use. Leave them out on a re-sync.

| Upstream rule | Why |
| --- | --- |
| `async-dependencies` | Recommends `better-all`, which is not a dependency |
| `async-defer-await`, `async-cheap-condition-before-await` | Request-handler patterns; client awaits live in TanStack `queryFn`s and mutations |
| `bundle-analyzable-paths` | No variable-path `import()`; Vite warns on dynamic imports it cannot analyse |
| `bundle-barrel-imports` | Rollup tree-shakes the repo's own barrel files in production |
| `bundle-preload` | An opt-in optimisation, not a defect |
| `client-event-listeners`, `client-passive-event-listeners` | No shared global or touch/wheel listeners worth deduplicating |
| `client-localstorage-schema`, `js-cache-storage` | `localStorage` holds a single auth-token string |
| all `js-*` rules | Micro-optimisations on arrays of 100 items or fewer, or style the model follows anyway |
| `rendering-animate-svg-wrapper`, `rendering-svg-precision` | No SVG animations; icons come from `lucide-react` |
| `rendering-content-visibility` | Lists are paginated at 100 rows at most |
| `rendering-hoist-jsx` | Micro-optimisation, never a defect |
| `rendering-resource-hints` | One-time setup, not something a diff breaches |
| `rendering-script-defer-async` | The only script tag is Vite's module script, already deferred |
| `rendering-usetransition-loading`, `rerender-transitions`, `rerender-use-deferred-value` | Loading state comes from TanStack `isPending`; reach for transitions when profiling shows jank |
| `rerender-defer-reads`, `rerender-dependencies`, `rerender-derived-state`, `rerender-memo`, `rerender-memo-with-default-value`, `rerender-simple-expression-in-memo`, `rerender-split-combined-hooks`, `rerender-use-ref-transient-values` | Re-render micro-optimisations: profile first, not review |
| `advanced-event-handler-refs` | Library-hook pattern that `useEffectEvent` replaces on React 19.2 |
| `async-parallel`, `rerender-functional-setstate`, `rerender-lazy-state-init`, `js-tosorted-immutable`, `bundle-dynamic-imports`, `advanced-init-once`, `rendering-conditional-render`, `react19-no-forwardref` (from `composition-patterns`) | Textbook React the model already follows; routes are already lazy through `createLazyRoute` |
| `architecture-avoid-boolean-props`, `architecture-compound-components`, `patterns-explicit-variants` (from `composition-patterns`), `rerender-no-inline-components`, `rerender-list-keys`, `bundle-conditional`, `bundle-defer-third-party`, `async-suspense-boundaries`, `client-query-dedup` (was `client-swr-dedup`) | General React and component-design advice the model already knows; `client-query-dedup` duplicated `repo-no-page-owned-server-state` |
| local additions `async-fetch-race-cleanup`, `client-external-store-sync`, `rerender-context-value-memo`; repo-skill carry-overs `client-query-options`, `rerender-store-id-derive-item` | Textbook React and TanStack Query; `async-fetch-race-cleanup` taught the hand-written fetch effect the repo rules forbid |

## Rewritten for this stack

| Rule | Change |
| --- | --- |

## Adapted (small edits)

- `rerender-lift-or-pass-down`: references to SSR, SWR and `server-*` rules removed.

## Local additions kept

Three rules that are not upstream, written from the react.dev documentation ("You Might Not Need an Effect"): `rerender-lift-or-pass-down`, `rerender-no-effect-chains`, `rerender-reset-state-with-key`.

## Added

- From the retired repo skill `react-best-practices` (TanStack Query and SPA guidance with no upstream equivalent): `client-query-keys`, `client-query-pending-vs-fetching`, `async-public-routes-no-auth-wait`.
- `repo-*` rules: each names its source section in `client/AGENTS.md` or `client/DESIGN.md`. Removed after review because the codebase already shows them: `repo-no-class-components`, `repo-no-redux`, `repo-router-v6-hooks`, `repo-no-bootstrap-utility-classes` (the retired packages are not installed), `repo-comments-block-contract` (`tsc` checks the props).

## Impact levels

Upstream's MEDIUM-HIGH is MEDIUM here and LOW-MEDIUM is LOW, so every rule maps to one review criticality.

## Re-syncing with upstream

1. Clone upstream at the new commit and diff `skills/react-best-practices/rules/` against the commit above.
2. Copy changed upstream rules over the matching files that still carry the plain upstream footer.
3. For files marked `adapted` or `rewritten`, merge by hand, re-applying the changes listed here.
4. Check new upstream rules against the stack assumptions before adding them, then add each to the `SKILL.md` index.
5. Update the commit in this file and in `SKILL.md`.
