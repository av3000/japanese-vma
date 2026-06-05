---
name: improve-frontend-codebase-architecture
description: Use when improving React client architecture, reviewing touched or migrated frontend surfaces, finding shallow route/hook/API modules, planning legacy-to-v1 frontend migration, or deciding where frontend seams should live.
---

# Improve Frontend Codebase Architecture

Surface frontend deepening opportunities: refactors that turn shallow route, hook, API, adapter, or UI modules into deeper modules with better locality, leverage, and test surfaces.

## Core Vocabulary

Use the architecture vocabulary from `improve-codebase-architecture`: **module**, **interface**, **seam**, **adapter**, **depth**, **leverage**, and **locality**. Apply it to frontend surfaces without drifting into vague "component/service/boundary" language.

Frontend-specific seam vocabulary lives in [references/frontend-seams.md](references/frontend-seams.md).

## Required Context

Read these first:

1. root `AGENTS.md`
2. `client/AGENTS.md`
3. `CONTEXT.md` and `docs/adr/` if they exist
4. Relevant repo-local frontend skills when triggered:
   - `client-frontend-standards`
   - `saved-list-to-catalogue-v1-migration`
   - `scramble-orval-contract-debugging`

Read reference files only when needed:

- [references/local-precedents.md](references/local-precedents.md) for current good, mixed, and legacy examples.
- [references/generated-client-boundaries.md](references/generated-client-boundaries.md) for Orval/v1 contract decisions.
- [references/state-management.md](references/state-management.md) for React Query, local state, Context, Zustand, and Redux guidance.
- [references/scalability.md](references/scalability.md) when the user asks whether a frontend architecture scales, or when a change affects route/API/state/provider patterns across multiple features.

## Process

### 1. Explore

Inspect the touched route, feature module, API hook, generated client, tests, and nearby precedents.

Look for friction:

- route modules owning request details, pagination, cache invalidation, or backend wire shape
- feature UI modules accumulating repeated server mutations or side effects
- wrappers that only rename generated clients
- temporary adapters without target endpoint, removal condition, or issue status
- legacy `apiCall(...)`, `@ts-nocheck`, class components, route-owned server pagination, or magic numbers copied forward
- tests that reach past the module interface instead of testing the seam where behavior lives
- scalability risks: low locality, high coordination cost, unstable data boundaries, broad provider rerenders, public routes coupled to private/auth state, or feature additions that grow the initial bundle

Apply the deletion test: if deleting a module removes ceremony, it is shallow; if deleting it pushes complexity into multiple callers, it is earning its keep.

### 2. Present Candidates

Present numbered deepening opportunities. For each candidate include:

- **Files** - exact paths
- **Problem** - what friction the current interface causes
- **Solution** - plain English change, not final interface design yet
- **Benefits** - locality, leverage, and testing improvement
- **Scalability** - current-size fit, near-term growth, and long-term risk
- **Migration risk** - contract, generated-client, or legacy behavior risk

Use [templates/deepening-candidate-report.md](templates/deepening-candidate-report.md) if the report has multiple candidates.

Do not implement yet. Ask: "Which candidate should we explore?"

### 3. Grill The Chosen Candidate

Once the user picks a candidate, walk the design tree:

- What behavior should sit behind the seam?
- Which callers should know the interface?
- Is the generated v1 client usable, missing, or unstable?
- Is backend/schema work required before frontend cleanup?
- What temporary adapter remains, and what removes it?
- What tests survive an internal refactor?

If interface alternatives are needed, use `improve-codebase-architecture` `INTERFACE-DESIGN.md` as the pattern: compare radically different interfaces by depth, locality, and seam placement.

## Default Frontend Shape

Use this as the default for touched/migrated work:

```text
route -> feature orchestration -> feature API module/hook -> generated client
```

Do not create `domain/`, `useCases/`, `Service`, `Manager`, or `Adapter` files by default. Add them only when the deletion test shows real leverage.

Scalable frontend architecture in this repo means added routes and features keep changes local, preserve clear route/API/state seams, avoid global coordination files, keep server state behind React Query/generated-client boundaries, tolerate legacy-to-v1 migration, remain testable at seam level, and do not grow first-load cost or provider rerender blast radius unnecessarily. For details, use [references/scalability.md](references/scalability.md).

## Optional Tools

Run the scanner on a focused file set when useful:

```powershell
.codex/skills/improve-frontend-codebase-architecture/scripts/check-frontend-architecture.ps1 -Files client/src/routes/ArticlesList/index.tsx
```

Use templates only after a candidate is chosen:

- [templates/feature-api-hook.ts](templates/feature-api-hook.ts)
- [templates/temporary-adapter.ts](templates/temporary-adapter.ts)

## Common Mistakes

| Mistake | Correction |
|---|---|
| Copying a clean-architecture folder diagram | Keep the repo shape; deepen seams where they earn leverage |
| Wrapping generated clients for naming symmetry | Use generated clients directly unless a hook adds behavior |
| Moving all logic out of UI blindly | Keep local UI state local; move repeated server behavior |
| Treating temporary adapters as normal architecture | Add target, removal condition, and issue/no-issue status |
| Testing every route end-to-end | Prefer seam-level tests; add route tests only for crossing behavior |
| Answering "does it scale?" with yes/no | Separate current-size fit, near-term scaling, and long-term risk |
