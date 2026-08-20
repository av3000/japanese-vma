# GitHub Issue Creation Template For Legacy To v1 Migration

Use this document when you are ready to turn `docs/legacy-v1-migration/backend-frontend-issue-backlog.md` into GitHub issues and subissues.

Tracker target: `av3000/japanese-vma`

Default label for approved implementation issues: `ready-for-agent`

## Recommended Workflow

1. Review `backend-frontend-issue-backlog.md` and approve the HITL decision issue `A0`.
2. Create one parent PRD issue in GitHub.
3. Create child issues in dependency order.
4. Link each child issue back to the parent issue.
5. Update the tracking table in this document with the GitHub issue numbers.
6. As each issue lands, update the status and migration evidence.

Do not publish every possible issue as `ready-for-agent` at once if a dependency is unresolved. Use `needs-triage` or leave it unpublished until the blocker has a real issue number and acceptance criteria.

## Parent PRD Issue Template

```markdown
## Problem Statement

The application still has large non-v1 Laravel surfaces and legacy React route modules. This keeps new work tied to old route/controller patterns, raw frontend API strings, and mixed response contracts.

## Solution

Migrate the remaining legacy areas in small backend and frontend slices. Backend slices create or deepen v1 modules with documented contracts, tests, and generated OpenAPI. Frontend slices move active routes to generated clients, React Query, React Router v6 hooks, and focused feature modules.

## Required Skills

- `$grill-with-docs`
- `$improve-codebase-architecture`
- `$improve-frontend-codebase-architecture`
- `$superpowers:writing-plans`

## Source Documents

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `docs/legacy-v1-migration/github-issue-creation-template.md`

## Scope

In scope:

- Article admin/status v1 cleanup.
- Catalogue legacy identity compatibility.
- SavedList route cleanup and catalogue item renderer rehome.
- Kanji schema/list/detail migration.
- Radicals v1 backend and frontend migration.
- Public word list/detail migration.
- Sentence list/detail/comment migration.
- Community post read/write/comment migration.
- Generic comment and like cleanup.
- `apiCall` retirement.
- Legacy route retirement audit after callers migrate.

Out of scope:

- Article word extraction / article word-processing migration.
- Broad UI redesign.
- Deleting all legacy routes at once.
- Production infrastructure changes.

## Acceptance Criteria

- [ ] Child issues are published in dependency order.
- [ ] Backend and frontend work are separated where a combined issue would be too large.
- [ ] Every child issue has acceptance criteria and blockers.
- [ ] Every implementation child issue is ready for `$superpowers:writing-plans`.
- [ ] Tracking table in `docs/legacy-v1-migration/github-issue-creation-template.md` is updated with issue numbers.
```

## Child Issue Template

Use this for each child issue extracted from `backend-frontend-issue-backlog.md`.

```markdown
## Parent

Parent: #<parent-prd-issue-number>

## Source

Source slice: `<Slice ID and title from docs/legacy-v1-migration/backend-frontend-issue-backlog.md>`

## Required Skills

- `$grill-with-docs` if this issue has unresolved terminology, domain behavior, or contract decisions. If code can answer the question, inspect code instead of asking.
- `$improve-codebase-architecture` for backend module/interface/seam decisions.
- `$improve-frontend-codebase-architecture` for frontend route/API/module decisions.
- `$superpowers:writing-plans` before implementation.

## What to build

<Concise end-to-end behavior for this slice. Avoid stale file-by-file instructions unless the issue is specifically about a file cleanup.>

## Acceptance criteria

- [ ] <Concrete behavior or contract criterion>
- [ ] <Concrete behavior or contract criterion>
- [ ] <Verification criterion>

## Blocked by

- <Issue number or "None - can start immediately">

## Notes for implementation planning

- Preserve current user-visible behavior unless this issue explicitly changes it.
- Fix backend schema before frontend type work if generated types are wrong.
- Do not hand-edit generated files.
- Do not delete legacy routes in the same issue unless this is a route-retirement issue and all callers are migrated.
```

## Example Child Issues

### Example 1: Backend Contract Issue

```markdown
## Parent

Parent: #123

## Source

Source slice: `B3: Fix v1 Kanji OpenAPI Schema For Generated Clients`

## Required Skills

- `$improve-codebase-architecture`
- `$superpowers:writing-plans`

## What to build

Correct the v1 kanji resource and collection schema so generated frontend clients receive structured kanji types instead of `string`. This issue should not change user-visible kanji behavior.

## Acceptance criteria

- [ ] `KanjiResource` schema exposes structured kanji fields.
- [ ] Kanji collection schema exposes structured list and pagination fields.
- [ ] Backend schema/unit tests prove the OpenAPI shape.
- [ ] `composer openapi` updates `processor-api/api.json`.
- [ ] `npm run orval:file` generates structured kanji client types.

## Blocked by

- #124
```

### Example 2: Frontend Migration Issue

```markdown
## Parent

Parent: #123

## Source

Source slice: `F4: Migrate Kanji List Route To v1 Query Module`

## Required Skills

- `$improve-frontend-codebase-architecture`
- `$superpowers:writing-plans`

## What to build

Rewrite the kanji list route as a function component using a typed query module or hook over the generated v1 kanji client. Server state should live in React Query and route JSX should not own backend wire-shape details.

## Acceptance criteria

- [ ] The kanji list route has no `@ts-nocheck`.
- [ ] The kanji list route has no class component state.
- [ ] The route no longer calls `apiCall`.
- [ ] Search, filter, and pagination behavior map to v1 query params.
- [ ] Focused frontend tests cover initial load, filters, pagination/load-more behavior, and empty state.
- [ ] `npm run typecheck` passes.

## Blocked by

- #125
```

## Tracking Model

Use this status vocabulary:

- `Not published`: planned locally but no GitHub issue exists yet.
- `Needs decision`: HITL decision still required.
- `Ready`: issue has clear scope and can receive `ready-for-agent`.
- `In progress`: implementation branch or agent work has started.
- `Backend done`: backend merged or ready, frontend still blocked/pending.
- `Frontend done`: frontend merged or ready, backend already done.
- `Blocked`: waiting on another issue, schema generation, environment, or decision.
- `Migrated`: v1 backend and frontend caller migration are both done.
- `Retired`: legacy route/caller has been removed or intentionally replaced.

## Migration Tracking Table

Update this table whenever issues are created or completed.

| Slice | Track | GitHub Issue | Status | Blocked By | Migrated Evidence | Legacy Remaining |
| --- | --- | --- | --- | --- | --- | --- |
| A0 Migration sequencing and skill preflight | HITL |  | Not published | None |  | Entire backlog unpublished |
| B1 Article admin/status backend | Backend |  | Not published | A0 |  | Legacy pending/status endpoints |
| F1 Article admin/dashboard frontend | Frontend |  | Not published | B1 |  | `setArticleStatus`, pending dashboard legacy call |
| B2 Catalogue legacy identity backend | Backend |  | Not published | A0 |  | `/api/list/{id}` compatibility lookup |
| F2 Catalogue legacy redirects frontend | Frontend |  | Not published | B2 |  | `legacyCatalogues.ts` raw lookup |
| F3 SavedList cleanup and item renderer rehome | Frontend |  | Not published | F2 |  | SavedList-named route/components |
| B3 Kanji schema correction backend | Backend |  | Not published | A0 |  | Generated kanji client typed as weak shape |
| F4 Kanji list frontend | Frontend |  | Not published | B3 |  | `KanjisList` legacy route |
| H1 Kanji detail aggregate decision | HITL |  | Not published | F4 |  | Detail aggregate unresolved |
| B4 Kanji detail aggregate backend | Backend |  | Not published | H1 |  | Legacy kanji detail aggregate |
| F5 Kanji detail frontend | Frontend |  | Not published | B4 |  | `KanjiDetails` legacy route |
| B5 Radicals backend | Backend |  | Not published | A0 |  | No v1 radical routes |
| F6 Radicals frontend | Frontend |  | Not published | B5 |  | Radical list/detail legacy routes |
| B6 Public words backend | Backend |  | Not published | A0 |  | No public v1 word routes |
| F7 Public words frontend | Frontend |  | Not published | B6 |  | Word list/detail legacy routes |
| B7 Sentences read/detail backend | Backend |  | Not published | A0 |  | No v1 sentence read/detail routes |
| F8 Sentences read/detail frontend | Frontend |  | Not published | B7 |  | Sentence list/detail legacy reads |
| B8 Sentence comments backend | Backend |  | Not published | B7 |  | No v1 sentence comment reads |
| F9 Sentence comments frontend | Frontend |  | Not published | B8 |  | Old sentence comment props/calls |
| B9 Post read backend | Backend |  | Not published | A0 |  | No v1 post read routes |
| F10 Post read frontend | Frontend |  | Not published | B9 |  | `PostsList`/post detail legacy reads |
| B10 Post write/moderation backend | Backend |  | Not published | B9 |  | Legacy post writes/lock |
| F11 Post write/moderation frontend | Frontend |  | Not published | B10, F10 |  | Post form/edit/delete/lock legacy UI |
| B11 Post comments backend | Backend |  | Not published | B9 |  | No v1 post comment reads |
| F12 Post comments frontend | Frontend |  | Not published | B11, F10 |  | Old post comment props/calls |
| B12 Comment delete/update backend | Backend |  | Not published | B8, B11 |  | Legacy comment delete/update routes |
| F13 Comment API cleanup frontend | Frontend |  | Not published | B12, F9, F12 |  | `comments.ts` custom/legacy URL construction |
| F14 Generic like generated-client cleanup | Frontend |  | Not published | Caller-specific backend readiness |  | Raw axios like wrapper |
| F15 `apiCall` retirement | Frontend |  | Not published | F1, F2, F4, F5, F6, F7, F8, F10, F11, F13 |  | `apiCall` production callers |
| B13 Legacy route retirement audit | Backend |  | Not published | F15 |  | `routes/api.php` legacy endpoints |

## How To Track What Is Migrated

Track migration at two levels:

1. **Issue status:** use the table above to track planning and implementation state.
2. **Evidence:** each completed row should name the concrete proof, such as:

- v1 route exists and is covered by backend feature tests.
- `processor-api/api.json` includes the v1 contract.
- Orval generated a usable client.
- frontend route no longer uses `apiCall`.
- frontend route no longer has `@ts-nocheck`.
- legacy route has no production caller.
- legacy route was removed or intentionally kept as compatibility.

Do not mark a slice `Migrated` until both backend contract and active frontend caller are on v1, unless the slice is backend-only by design.

Do not mark a legacy route `Retired` until a grep confirms no production frontend caller and the route has no documented compatibility requirement.

## Suggested Commands For Tracking

Use these commands during tracking reviews:

```powershell
rg -n "apiCall" client/src
rg -n "@ts-nocheck|componentWillMount|props\\.match|props\\.history" client/src/routes client/src/components/features
rg -n "Route::" processor-api/routes/api.php processor-api/routes/api_v1.php
rg -n "/api/" client/src
rg -n "/list/|/post/|/sentence/|/word/|/radical/|/kanji/" client/src
```

Use these after backend contract changes:

```powershell
cd processor-api
docker compose exec laravel-app composer openapi
```

Use these after generated frontend client changes:

```powershell
npm run orval:file
npm run typecheck
```
